<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();

$pdo = getDb();
$seccionActual = 'suscriptores';
$tituloPagina = 'Suscriptores';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrar'])) {
    exigirCsrf();
    $pdo->prepare('DELETE FROM suscriptores WHERE id = ?')->execute([(int)$_POST['borrar']]);
    redirigir('suscriptores.php?ok=1');
}

$suscriptores = $pdo->query('SELECT * FROM suscriptores ORDER BY fecha DESC')->fetchAll();

require __DIR__ . '/includes/layout_header.php';
?>

<div class="barra-superior">
  <h1 style="margin:0;">Suscriptores (<?= count($suscriptores) ?>)</h1>
  <?php if ($suscriptores): ?>
    <a href="suscriptores_exportar.php" class="btn secundario">Descargar CSV</a>
  <?php endif; ?>
</div>
<p style="color:var(--gris);font-size:14px;max-width:60ch;margin-top:-14px;">
  Personas que se han apuntado desde el formulario del pie de la web
  para recibir avisos de noticias nuevas. Esta web no envía los
  correos por sí sola: exporta la lista y envíalos desde tu propio
  correo o una herramienta de newsletter.
</p>

<?php if (isset($_GET['ok'])): ?>
  <div class="aviso ok">Cambios guardados correctamente.</div>
<?php endif; ?>

<table class="admin-tabla">
  <thead>
    <tr><th>Email</th><th>Fecha</th><th></th></tr>
  </thead>
  <tbody>
    <?php foreach ($suscriptores as $s): ?>
    <tr>
      <td><?= e($s['email']) ?></td>
      <td><?= e($s['fecha']) ?></td>
      <td class="acciones">
        <form method="post" onsubmit="return confirm('¿Borrar este suscriptor?');">
          <?= campoCsrf() ?>
          <input type="hidden" name="borrar" value="<?= (int)$s['id'] ?>">
          <button type="submit" class="borrar">Borrar</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$suscriptores): ?>
      <tr><td colspan="3">Todavía no hay suscriptores.</td></tr>
    <?php endif; ?>
  </tbody>
</table>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
