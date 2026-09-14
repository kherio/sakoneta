<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();

$pdo = getDb();
$seccionActual = 'mensajes';
$tituloPagina = 'Mensajes de contacto';

if (isset($_GET['marcar'])) {
    exigirCsrf();
    $stmt = $pdo->prepare('UPDATE mensajes_contacto SET leido = 1 WHERE id = ?');
    $stmt->execute([(int)$_GET['marcar']]);
    redirigir('mensajes.php');
}
if (isset($_GET['borrar'])) {
    exigirCsrf();
    $stmt = $pdo->prepare('DELETE FROM mensajes_contacto WHERE id = ?');
    $stmt->execute([(int)$_GET['borrar']]);
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
        <?php if (!$m['leido']): ?><a href="mensajes.php?marcar=<?= (int)$m['id'] ?>&csrf_token=<?= e(tokenCsrf()) ?>" class="editar">Marcar leído</a><?php endif; ?>
        <a href="mensajes.php?borrar=<?= (int)$m['id'] ?>&csrf_token=<?= e(tokenCsrf()) ?>" class="borrar" onclick="return confirm('¿Borrar este mensaje?');">Borrar</a>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$mensajes): ?>
      <tr><td colspan="5">Todavía no se ha recibido ningún mensaje.</td></tr>
    <?php endif; ?>
  </tbody>
</table>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
