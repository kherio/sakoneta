<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDb();

$noticiaId = (int)($_POST['noticia_id'] ?? 0);
$nombre = trim($_POST['nombre'] ?? '');
$email = trim($_POST['email'] ?? '');
$mensaje = trim($_POST['mensaje'] ?? '');
$senuelo = trim($_POST['web'] ?? '');
$volver = $_POST['volver'] ?? ('noticia.php?id=' . $noticiaId);

// Ruta de vuelta solo relativa dentro del propio sitio (evita redirecciones abiertas)
if ($volver === '' || strpos($volver, '://') !== false || strpos($volver, '//') === 0) {
    $volver = 'noticia.php?id=' . $noticiaId;
}
$separador = (strpos($volver, '?') !== false) ? '&' : '?';

// Si el campo señuelo viene relleno, es casi seguro un bot: fingimos éxito y no guardamos nada
if ($senuelo !== '') {
    redirigir($volver . $separador . 'comentario=enviado');
}

$stmt = $pdo->prepare('SELECT id FROM noticias WHERE id = ? AND publicado = 1');
$stmt->execute([$noticiaId]);

if (!$stmt->fetchColumn() || $nombre === '' || $mensaje === '' || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))) {
    redirigir($volver . $separador . 'comentario=error');
}

$stmt = $pdo->prepare("INSERT INTO comentarios (noticia_id, nombre, email, mensaje, fecha, estado) VALUES (?,?,?,?,?,'pendiente')");
$stmt->execute([$noticiaId, $nombre, $email ?: null, $mensaje, date('Y-m-d H:i:s')]);

redirigir($volver . $separador . 'comentario=enviado');
