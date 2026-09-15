<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

$pdo = getDb();
$id = (int)($_POST['id'] ?? 0);

$respuesta = ['ok' => false];

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT id, likes FROM noticias WHERE id = ? AND publicado = 1');
    $stmt->execute([$id]);
    $noticia = $stmt->fetch();

    if ($noticia) {
        $nombreCookie = 'like_noticia_' . $id;
        $yaLeGusta = isset($_COOKIE[$nombreCookie]);

        if (!$yaLeGusta) {
            $pdo->prepare('UPDATE noticias SET likes = likes + 1 WHERE id = ?')->execute([$id]);
            setcookie($nombreCookie, '1', time() + 60 * 60 * 24 * 365, '/');
            $noticia['likes']++;
        }

        $respuesta = ['ok' => true, 'likes' => (int)$noticia['likes'], 'yaLeGusta' => true];
    }
}

echo json_encode($respuesta);
