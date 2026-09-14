<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();

$pdo = getDb();
$seccionActual = 'noticias';
$tituloPagina = 'Noticias';

$buscar = trim($_GET['buscar'] ?? '');
if ($buscar !== '') {
    $stmt = $pdo->prepare('SELECT * FROM noticias WHERE titulo LIKE ? OR resumen LIKE ? ORDER BY fecha DESC');
    $comodin = '%' . $buscar . '%';
    $stmt->execute([$comodin, $comodin]);
    $noticias = $stmt->fetchAll();
} else {
    $noticias = $pdo->query('SELECT * FROM noticias ORDER BY fecha DESC')->fetchAll();
}

require __DIR__ . '/includes/layout_header.php';
?>

<div class="barra-superior">
  <h1 style="margin:0;">Noticias</h1>
  <a href="noticia_form.php" class="btn">+ Nueva noticia</a>
</div>

<?php if (isset($_GET['ok'])): ?>
  <div class="aviso ok">Cambios guardados correctamente.</div>
<?php endif; ?>

<form method="get" class="barra-busqueda">
  <input type="text" name="buscar" value="<?= e($buscar) ?>" placeholder="Buscar por título o resumen...">
  <button type="submit" class="btn secundario">Buscar</button>
  <?php if ($buscar !== ''): ?><a href="noticias.php" class="btn secundario">Limpiar</a><?php endif; ?>
</form>

<table class="admin-tabla">
  <thead>
    <tr><th>Título</th><th>Fecha</th><th>Estado</th><th></th></tr>
  </thead>
  <tbody>
    <?php foreach ($noticias as $n): ?>
    <tr>
      <td><?= e($n['titulo']) ?></td>
      <td><?= e($n['fecha']) ?></td>
      <td><?= $n['publicado'] ? 'Publicada' : 'Borrador' ?></td>
      <td class="acciones">
        <a href="noticia_form.php?id=<?= (int)$n['id'] ?>" class="editar">Editar</a>
        <a href="noticia_borrar.php?id=<?= (int)$n['id'] ?>&csrf_token=<?= e(tokenCsrf()) ?>" class="borrar" onclick="return confirm('¿Seguro que quieres borrar esta noticia?');">Borrar</a>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$noticias): ?>
      <tr><td colspan="4"><?= $buscar !== '' ? 'No hay noticias que coincidan con la búsqueda.' : 'Todavía no hay noticias.' ?></td></tr>
    <?php endif; ?>
  </tbody>
</table>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
