<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if (estaAutenticado()) {
    header('Location: dashboard.php');
    exit;
}

$pdo = getDb();

$error = '';
$minutosRestantes = minutosBloqueoRestantes($pdo);

if ($minutosRestantes > 0) {
    $error = 'Demasiados intentos fallidos. Vuelve a probar en unos ' . $minutosRestantes . ' minutos.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenValido = !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $clave = $_POST['clave'] ?? '';

    $stmtUsuario = $pdo->prepare('SELECT * FROM usuarios WHERE usuario = ? AND activo = 1');
    $stmtUsuario->execute([$usuario]);
    $filaUsuario = $stmtUsuario->fetch();

    if ($tokenValido && $filaUsuario && password_verify($clave, $filaUsuario['password_hash'])) {
        resetearIntentosLogin($pdo);
        session_regenerate_id(true);
        $_SESSION['admin_autenticado'] = true;
        $_SESSION['admin_usuario'] = $filaUsuario['usuario'];
        $_SESSION['admin_usuario_id'] = (int)$filaUsuario['id'];
        $_SESSION['admin_nombre'] = $filaUsuario['nombre'];
        $_SESSION['admin_rol'] = $filaUsuario['rol'];
        unset($_SESSION['csrf_token']);
        header('Location: dashboard.php');
        exit;
    }

    registrarIntentoFallido($pdo);
    usleep(700000);
    $minutosRestantes = minutosBloqueoRestantes($pdo);
    $error = $minutosRestantes > 0
        ? 'Demasiados intentos fallidos. Vuelve a probar en unos ' . $minutosRestantes . ' minutos.'
        : 'Usuario o contraseña incorrectos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Acceso administración · <?= htmlspecialchars(nombreSitio()) ?></title>
<link rel="stylesheet" href="css/admin.css">
</head>
<body>
<div class="login-pantalla">
  <form method="post" class="login-caja">
    <?= campoCsrf() ?>
    <img src="../img/logo-sakoneta.png" alt="">
    <h1>Panel de administración</h1>
    <p class="subt"><?= htmlspecialchars(nombreSitio()) ?></p>

    <?php if ($error): ?>
      <div class="aviso error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="campo">
      <label for="usuario">Usuario</label>
      <input type="text" id="usuario" name="usuario" required autofocus autocomplete="username">
    </div>
    <div class="campo">
      <label for="clave">Contraseña</label>
      <input type="password" id="clave" name="clave" required autocomplete="current-password">
    </div>
    <button type="submit" class="btn ancho">Entrar</button>
  </form>
</div>
</body>
</html>
