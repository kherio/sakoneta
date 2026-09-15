<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();
exigirRol(['administrador','editor']);

$pdo = getDb();
$seccionActual = 'competiciones';
$tituloPagina = 'Competiciones';

$buscar = trim($_GET['buscar'] ?? '');
$categoriaFiltro = trim($_GET['categoria'] ?? '');
$categorias = $pdo->query('SELECT nombre FROM categorias ORDER BY orden ASC')->fetchAll(PDO::FETCH_COLUMN);

$sql = 'SELECT * FROM competiciones WHERE 1=1';
$parametros = [];
if ($buscar !== '') {
    $sql .= ' AND (nombre LIKE ? OR lugar LIKE ?)';
    $comodin = '%' . $buscar . '%';
    $parametros[] = $comodin;
    $parametros[] = $comodin;
}
if ($categoriaFiltro !== '') {
    $sql .= ' AND categoria = ?';
    $parametros[] = $categoriaFiltro;
}
$sql .= ' ORDER BY fecha ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$competiciones = $stmt->fetchAll();
$sinFiltros = $buscar === '' && $categoriaFiltro === '';

require __DIR__ . '/includes/layout_header.php';
?>

<div class="barra-superior">
  <h1 style="margin:0;">Competiciones</h1>
  <a href="competicion_form.php" class="btn">+ Nueva competición</a>
</div>

<?php if (isset($_GET['ok'])): ?>
  <div class="aviso ok">Cambios guardados correctamente.</div>
<?php endif; ?>

<form method="get" class="barra-busqueda">
  <input type="text" name="buscar" value="<?= e($buscar) ?>" placeholder="Buscar por nombre o lugar...">
  <select name="categoria" onchange="this.form.submit()">
    <option value="">Todas las categorías</option>
    <?php foreach ($categorias as $c): ?>
      <option value="<?= e($c) ?>" <?= $categoriaFiltro === $c ? 'selected' : '' ?>><?= e($c) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn secundario">Buscar</button>
  <?php if (!$sinFiltros): ?><a href="competiciones.php" class="btn secundario">Limpiar</a><?php endif; ?>
</form>

<table class="admin-tabla">
  <thead>
    <tr><th></th><th>Fecha</th><th>Competición</th><th>Categoría</th><th>Lugar</th><th>Resultado</th><th></th></tr>
  </thead>
  <tbody>
    <?php foreach ($competiciones as $c): ?>
    <tr>
      <td style="width:56px;">
        <?php if (!empty($c['imagen_portada'])): ?>
          <img src="../img/<?= e($c['imagen_portada']) ?>" alt="" style="width:44px;height:44px;object-fit:cover;border-radius:6px;">
        <?php endif; ?>
      </td>
      <td><?= e($c['fecha']) ?></td>
      <td><?= e($c['nombre']) ?></td>
      <td><?= e($c['categoria']) ?></td>
      <td><?= e($c['lugar']) ?></td>
      <td><?= $c['disputada'] ? e($c['resultado'] ?: 'Disputada') : 'Pendiente' ?></td>
      <td class="acciones">
        <a href="competicion_form.php?id=<?= (int)$c['id'] ?>" class="editar">Editar</a>
        <form method="post" action="competicion_borrar.php" onsubmit="return confirm('¿Seguro que quieres borrar esta competición?');">
          <?= campoCsrf() ?>
          <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
          <button type="submit" class="borrar">Borrar</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$competiciones): ?>
      <tr><td colspan="7"><?= $sinFiltros ? 'Todavía no hay competiciones.' : 'No hay competiciones que coincidan con el filtro.' ?></td></tr>
    <?php endif; ?>
  </tbody>
</table>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
