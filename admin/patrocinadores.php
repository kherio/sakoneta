<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();

$pdo = getDb();
$seccionActual = 'patrocinadores';
$tituloPagina = 'Patrocinadores';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_patrocinador'])) {
    exigirCsrf();
    $nombre = trim($_POST['nuevo_patrocinador']);
    if ($nombre === '') {
        $error = 'Escribe un nombre para el patrocinador.';
    } else {
        $maxOrden = (int)$pdo->query('SELECT COALESCE(MAX(orden), 0) m FROM patrocinadores')->fetch()['m'];
        $stmt = $pdo->prepare('INSERT INTO patrocinadores (nombre, orden) VALUES (?, ?)');
        $stmt->execute([$nombre, $maxOrden + 1]);
        redirigir('patrocinadores.php?ok=1');
    }
}

$patrocinadores = $pdo->query('SELECT * FROM patrocinadores ORDER BY orden ASC')->fetchAll();

require __DIR__ . '/includes/layout_header.php';
?>

<h1>Patrocinadores</h1>
<p style="color:var(--gris);font-size:14px;max-width:60ch;margin-top:-14px;">
  Aparecen en el pie de página de toda la web. Añade el nombre aquí y
  luego entra a "Editar" para subir el logo y, si quieres, un enlace
  a su página.
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
    <label for="nuevo_patrocinador">Nuevo patrocinador</label>
    <input type="text" id="nuevo_patrocinador" name="nuevo_patrocinador" placeholder="Nombre de la empresa">
  </div>
  <button type="submit" class="btn">Añadir</button>
</form>

<table class="admin-tabla">
  <thead>
    <tr><th style="width:70px;"></th><th>Nombre</th><th>Enlace</th><th></th></tr>
  </thead>
  <tbody>
    <?php foreach ($patrocinadores as $p): ?>
    <tr>
      <td>
        <?php if (!empty($p['logo'])): ?>
          <img src="../img/<?= e($p['logo']) ?>" alt="" style="max-width:56px;max-height:40px;object-fit:contain;">
        <?php endif; ?>
      </td>
      <td><?= e($p['nombre']) ?></td>
      <td><?= $p['url'] ? '<a href="' . e($p['url']) . '" target="_blank">' . e($p['url']) . '</a>' : '—' ?></td>
      <td class="acciones">
        <a href="patrocinador_form.php?id=<?= (int)$p['id'] ?>" class="editar">Editar</a>
        <form method="post" action="patrocinador_borrar.php" onsubmit="return confirm('¿Borrar este patrocinador?');">
          <?= campoCsrf() ?>
          <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
          <button type="submit" class="borrar">Borrar</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$patrocinadores): ?>
      <tr><td colspan="4">Todavía no hay patrocinadores. Añade el primero arriba.</td></tr>
    <?php endif; ?>
  </tbody>
</table>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
