<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();
exigirRol(['administrador','moderador']);

$pdo = getDb();
$seccionActual = 'mensajes';
$tituloPagina = 'Mensajes de contacto';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['marcar'])) {
    exigirCsrf();
    $stmt = $pdo->prepare('UPDATE mensajes_contacto SET leido = 1 WHERE id = ?');
    $stmt->execute([(int)$_POST['marcar']]);
    redirigir('mensajes.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrar'])) {
    exigirCsrf();
    $stmt = $pdo->prepare('DELETE FROM mensajes_contacto WHERE id = ?');
    $stmt->execute([(int)$_POST['borrar']]);
    redirigir('mensajes.php');
}

$mensajes = $pdo->query('SELECT * FROM mensajes_contacto ORDER BY fecha DESC')->fetchAll();

require __DIR__ . '/includes/layout_header.php';
?>

<h1>Mensajes de contacto</h1>

<table class="admin-tabla">
  <thead>
    <tr><th>Fecha</th><th>Nombre</th><th>Email</th><th>Mensaje</th><th></th></tr>
  </thead>
  <tbody>
    <?php foreach ($mensajes as $m): ?>
    <tr style="<?= $m['leido'] ? 'opacity:.6' : 'font-weight:600' ?>">
      <td><?= e($m['fecha']) ?></td>
      <td><?= e($m['nombre']) ?></td>
      <td><?= e($m['email']) ?></td>
      <td style="max-width:280px;"><?= e($m['mensaje']) ?></td>
      <td class="acciones">
        <?php if (!$m['leido']): ?>
        <form method="post">
          <?= campoCsrf() ?>
          <input type="hidden" name="marcar" value="<?= (int)$m['id'] ?>">
          <button type="submit" class="editar">Marcar leído</button>
        </form>
        <?php endif; ?>
        <form method="post" onsubmit="return confirm('¿Borrar este mensaje?');">
          <?= campoCsrf() ?>
          <input type="hidden" name="borrar" value="<?= (int)$m['id'] ?>">
          <button type="submit" class="borrar">Borrar</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$mensajes): ?>
      <tr><td colspan="5">Todavía no se ha recibido ningún mensaje.</td></tr>
    <?php endif; ?>
  </tbody>
</table>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
