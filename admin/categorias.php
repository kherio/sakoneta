<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();
exigirRol(['administrador','editor']);

$pdo = getDb();
$seccionActual = 'categorias';
$tituloPagina = 'Categorías';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nueva_categoria'])) {
    exigirCsrf();
    $nombre = trim($_POST['nueva_categoria']);
    if ($nombre === '') {
        $error = 'Escribe un nombre para la categoría.';
    } else {
        try {
            $maxOrden = (int)$pdo->query('SELECT COALESCE(MAX(orden), 0) m FROM categorias')->fetch()['m'];
            $stmt = $pdo->prepare('INSERT INTO categorias (nombre, orden) VALUES (?, ?)');
            $stmt->execute([$nombre, $maxOrden + 1]);
            redirigir('categorias.php?ok=1');
        } catch (PDOException $e) {
            $error = 'Ya existe una categoría con ese nombre.';
        }
    }
}

$categorias = $pdo->query('SELECT * FROM categorias ORDER BY orden ASC')->fetchAll();

require __DIR__ . '/includes/layout_header.php';
?>

<h1>Categorías</h1>
<p style="color:var(--gris);font-size:14px;max-width:60ch;margin-top:-14px;">
  Estas categorías son las que podrás elegir al dar de alta gimnastas y competiciones.
</p>

<?php if (isset($_GET['ok'])): ?>
  <div class="aviso ok">Cambios guardados correctamente.</div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="aviso error"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" style="display:flex;gap:12px;align-items:flex-end;margin-bottom:26px;max-width:460px;">
  <?= campoCsrf() ?>
  <div class="campo" style="flex:1;margin-bottom:0;">
    <label for="nueva_categoria">Nueva categoría</label>
    <input type="text" id="nueva_categoria" name="nueva_categoria" placeholder="Ej: Benjamín">
  </div>
  <button type="submit" class="btn">Añadir</button>
</form>

<?php if (count($categorias) > 1): ?>
  <p style="font-size:13px;color:var(--gris);margin-bottom:10px;">Arrastra las filas por el icono ⠿ para cambiar el orden en que aparecen en los desplegables.</p>
<?php endif; ?>
<div class="aviso ok" id="aviso-orden-guardado" style="display:none;">Orden actualizado.</div>

<table class="admin-tabla" id="tabla-categorias" data-endpoint-orden="categoria_reordenar.php" data-csrf="<?= e(tokenCsrf()) ?>">
  <thead>
    <tr><th style="width:30px;"></th><th>Nombre</th><th></th></tr>
  </thead>
  <tbody id="cuerpo-tabla-arrastrable">
    <?php foreach ($categorias as $c): ?>
    <tr data-id="<?= (int)$c['id'] ?>" draggable="true">
      <td class="asa-arrastrar" title="Arrastrar para reordenar">⠿</td>
      <td><?= e($c['nombre']) ?></td>
      <td class="acciones">
        <a href="../categoria.php?nombre=<?= urlencode($c['nombre']) ?>" target="_blank">Ver</a>
        <a href="categoria_form.php?id=<?= (int)$c['id'] ?>" class="editar">Editar</a>
        <form method="post" action="categoria_borrar.php" onsubmit="return confirm('¿Borrar esta categoría? Las gimnastas o competiciones que ya la usen mantendrán el nombre como texto suelto.');">
          <?= campoCsrf() ?>
          <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
          <button type="submit" class="borrar">Borrar</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$categorias): ?>
      <tr><td colspan="3">Todavía no hay categorías. Añade la primera arriba.</td></tr>
    <?php endif; ?>
  </tbody>
</table>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
