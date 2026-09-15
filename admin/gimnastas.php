<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();

$pdo = getDb();
$seccionActual = 'gimnastas';
$tituloPagina = 'Gimnastas';

$buscar = trim($_GET['buscar'] ?? '');
$categoriaFiltro = trim($_GET['categoria'] ?? '');
$categorias = $pdo->query('SELECT nombre FROM categorias ORDER BY orden ASC')->fetchAll(PDO::FETCH_COLUMN);

$sql = 'SELECT * FROM gimnastas WHERE 1=1';
$parametros = [];
if ($buscar !== '') {
    $sql .= ' AND (nombre LIKE ? OR aparato LIKE ?)';
    $comodin = '%' . $buscar . '%';
    $parametros[] = $comodin;
    $parametros[] = $comodin;
}
if ($categoriaFiltro !== '') {
    $sql .= ' AND categoria = ?';
    $parametros[] = $categoriaFiltro;
}
$sql .= ' ORDER BY orden ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$gimnastas = $stmt->fetchAll();

$sinFiltros = $buscar === '' && $categoriaFiltro === '';

require __DIR__ . '/includes/layout_header.php';
?>

<div class="barra-superior">
  <h1 style="margin:0;">Gimnastas</h1>
  <a href="gimnasta_form.php" class="btn">+ Nueva gimnasta</a>
</div>

<?php if (isset($_GET['ok'])): ?>
  <div class="aviso ok">Cambios guardados correctamente.</div>
<?php endif; ?>
<div class="aviso ok" id="aviso-orden-guardado" style="display:none;">Orden actualizado.</div>

<form method="get" class="barra-busqueda">
  <input type="text" name="buscar" value="<?= e($buscar) ?>" placeholder="Buscar por nombre o aparato...">
  <select name="categoria" onchange="this.form.submit()">
    <option value="">Todas las categorías</option>
    <?php foreach ($categorias as $c): ?>
      <option value="<?= e($c) ?>" <?= $categoriaFiltro === $c ? 'selected' : '' ?>><?= e($c) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn secundario">Buscar</button>
  <?php if (!$sinFiltros): ?><a href="gimnastas.php" class="btn secundario">Limpiar</a><?php endif; ?>
</form>

<?php if ($sinFiltros && count($gimnastas) > 1): ?>
  <p style="font-size:13px;color:var(--gris);margin-bottom:10px;">Arrastra las filas por el icono ⠿ para cambiar el orden en que aparecen en la web.</p>
<?php endif; ?>

<table class="admin-tabla" id="tabla-gimnastas" data-endpoint-orden="gimnasta_reordenar.php" data-csrf="<?= e(tokenCsrf()) ?>">
  <thead>
    <tr><?php if ($sinFiltros): ?><th style="width:30px;"></th><?php endif; ?><th>Nombre</th><th>Categoría</th><th>Modalidad</th><th>Aparato</th><th></th></tr>
  </thead>
  <tbody id="cuerpo-tabla-arrastrable">
    <?php foreach ($gimnastas as $g): ?>
    <tr data-id="<?= (int)$g['id'] ?>" <?= $sinFiltros ? 'draggable="true"' : '' ?>>
      <?php if ($sinFiltros): ?><td class="asa-arrastrar" title="Arrastrar para reordenar">⠿</td><?php endif; ?>
      <td><?= e($g['nombre']) ?></td>
      <td><?= e($g['categoria']) ?></td>
      <td><?= e($g['modalidad']) ?></td>
      <td><?= e($g['aparato']) ?></td>
      <td class="acciones">
        <a href="gimnasta_form.php?id=<?= (int)$g['id'] ?>" class="editar">Editar</a>
        <form method="post" action="gimnasta_borrar.php" onsubmit="return confirm('¿Seguro que quieres borrar esta gimnasta?');">
          <?= campoCsrf() ?>
          <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
          <button type="submit" class="borrar">Borrar</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$gimnastas): ?>
      <tr><td colspan="6">No hay gimnastas que coincidan con el filtro.</td></tr>
    <?php endif; ?>
  </tbody>
</table>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
