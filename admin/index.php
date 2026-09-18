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
$usuarioTecleado = trim($_POST['usuario'] ?? '');
$minutosRestantes = minutosBloqueoRestantesIp($pdo, ipVisitante());

if ($minutosRestantes > 0) {
    $error = 'Demasiados intentos fallidos. Vuelve a probar en unos ' . $minutosRestantes . ' minutos.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenValido = !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '');
    $clave = $_POST['clave'] ?? '';

    $resultado = intentarLogin($pdo, ipVisitante(), $usuarioTecleado, function () use ($pdo, $tokenValido, $usuarioTecleado, $clave) {
        if (!$tokenValido) return null;
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE usuario = ? AND activo = 1');
        $stmt->execute([$usuarioTecleado]);
        $fila = $stmt->fetch();
        return ($fila && password_verify($clave, $fila['password_hash'])) ? $fila : null;
    });

    if ($resultado['usuario']) {
        $filaUsuario = $resultado['usuario'];
        session_regenerate_id(true);
        $_SESSION['admin_autenticado'] = true;
        $_SESSION['admin_usuario'] = $filaUsuario['usuario'];
        $_SESSION['admin_usuario_id'] = (int)$filaUsuario['id'];
        $_SESSION['admin_nombre'] = $filaUsuario['nombre'];
        $_SESSION['admin_rol'] = $filaUsuario['rol'];
        $_SESSION['admin_session_version'] = (int)$filaUsuario['session_version'];
        $_SESSION['es_tecnico'] = (bool)($filaUsuario['es_tecnico'] ?? false);
        unset($_SESSION['csrf_token']);
        header('Location: dashboard.php');
        exit;
    }

    usleep(700000);
    $error = $resultado['minutos'] > 0
        ? 'Demasiados intentos fallidos. Vuelve a probar en unos ' . $resultado['minutos'] . ' minutos.'
        : 'Usuario o contraseña incorrectos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Acceso administración · <?= htmlspecialchars(nombreSitio()) ?></title>
<link rel="stylesheet" href="<?= versionArchivo('css/admin.css', 'admin/css/admin.css') ?>">
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
    <?php elseif (isset($_GET['cerrada'])): ?>
      <div class="aviso error">Tu sesión se ha cerrado (la cuenta se desactivó, cambió de rol de forma incompatible, o se cambió la contraseña desde otro sitio). Vuelve a entrar.</div>
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
