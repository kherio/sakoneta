<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}

// Sin esto, cualquier otro sitio podría enviar este mismo POST desde
// la página de un visitante y manipular el contador de "me gusta"
// sin que la persona se diera cuenta. El impacto de verdad es bajo
// (es un contador público, no hay cuentas ni datos de por medio),
// pero exigir el token es sencillo y cierra el hueco igualmente.
$token = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
    http_response_code(403);
    echo json_encode(['ok' => false]);
    exit;
}

$pdo = getDb();
$id = (int)($_POST['id'] ?? 0);

// Mismos atributos de seguridad que la cookie de sesión (ver
// config.php): no accesible desde JavaScript, no se envía en
// peticiones de terceros, y solo por HTTPS si el sitio ya usa HTTPS.
$cookieSegura = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

$respuesta = ['ok' => false];

if ($id > 0 && !superaLimiteEnvios($pdo, 'like', 30, 5)) {
    $stmt = $pdo->prepare('SELECT id, likes FROM noticias WHERE id = ? AND publicado = 1');
    $stmt->execute([$id]);
    $noticia = $stmt->fetch();

    if ($noticia) {
        $nombreCookie = 'like_noticia_' . $id;
        $yaLeGusta = isset($_COOKIE[$nombreCookie]);

        if ($yaLeGusta) {
            $pdo->prepare('UPDATE noticias SET likes = MAX(likes - 1, 0) WHERE id = ?')->execute([$id]);
            setcookie($nombreCookie, '', ['expires' => time() - 3600, 'path' => '/', 'secure' => $cookieSegura, 'httponly' => true, 'samesite' => 'Lax']);
            $noticia['likes'] = max($noticia['likes'] - 1, 0);
            $respuesta = ['ok' => true, 'likes' => (int)$noticia['likes'], 'yaLeGusta' => false];
        } else {
            $pdo->prepare('UPDATE noticias SET likes = likes + 1 WHERE id = ?')->execute([$id]);
            setcookie($nombreCookie, '1', ['expires' => time() + 60 * 60 * 24 * 365, 'path' => '/', 'secure' => $cookieSegura, 'httponly' => true, 'samesite' => 'Lax']);
            $noticia['likes']++;
            $respuesta = ['ok' => true, 'likes' => (int)$noticia['likes'], 'yaLeGusta' => true];
        }
    }
}

echo json_encode($respuesta);
