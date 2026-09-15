<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();

$pdo = getDb();
$seccionActual = 'cambiar_clave';
$tituloPagina = 'Cambiar contraseña';

$miId = (int)($_SESSION['admin_usuario_id'] ?? 0);
$stmtYo = $pdo->prepare('SELECT * FROM usuarios WHERE id = ?');
$stmtYo->execute([$miId]);
$yo = $stmtYo->fetch();

if (!$yo) {
    // Sesión de antes de tener usuarios con id (poco probable, pero por si acaso)
    redirigir('logout.php');
}

$error = '';
$exito = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf();
    $actual = $_POST['actual'] ?? '';
    $nueva = $_POST['nueva'] ?? '';
    $repetir = $_POST['repetir'] ?? '';

    if (!password_verify($actual, $yo['password_hash'])) {
        $error = 'La contraseña actual no es correcta.';
    } elseif (strlen($nueva) < 8) {
        $error = 'La contraseña nueva debe tener al menos 8 caracteres.';
    } elseif ($nueva !== $repetir) {
        $error = 'La nueva contraseña y su repetición no coinciden.';
    } else {
        $nuevoHash = password_hash($nueva, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?')->execute([$nuevoHash, $miId]);
        $exito = true;
    }
}

require __DIR__ . '/includes/layout_header.php';
?>

<h1>Cambiar contraseña</h1>
<p style="color:var(--gris);font-size:14px;max-width:60ch;margin-top:-14px;">
  Tu usuario de acceso es <strong><?= e($yo['usuario']) ?></strong>
  (rol: <?= e($yo['rol']) ?>); aquí solo se cambia tu contraseña.
</p>

<?php if ($exito): ?>
  <div class="aviso ok">Contraseña actualizada correctamente. Úsala la próxima vez que entres.</div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="aviso error"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" style="max-width:420px;">
  <?= campoCsrf() ?>
  <div class="campo">
    <label for="actual">Contraseña actual</label>
    <input type="password" id="actual" name="actual" required autocomplete="current-password">
  </div>
  <div class="campo">
    <label for="nueva">Contraseña nueva (mínimo 8 caracteres)</label>
    <input type="password" id="nueva" name="nueva" required minlength="8" autocomplete="new-password">
  </div>
  <div class="campo">
    <label for="repetir">Repite la contraseña nueva</label>
    <input type="password" id="repetir" name="repetir" required minlength="8" autocomplete="new-password">
  </div>
  <button type="submit" class="btn">Cambiar contraseña</button>
</form>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
