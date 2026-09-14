<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();

$pdo = getDb();
$seccionActual = 'categorias';
$tituloPagina = 'Editar categoría';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM categorias WHERE id = ?');
$stmt->execute([$id]);
$categoria = $stmt->fetch();

if (!$categoria) {
    redirigir('categorias.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf();
    $nombre = trim($_POST['nombre'] ?? '');
    $orden = (int)($_POST['orden'] ?? 0);

    if ($nombre === '') {
        $error = 'El nombre no puede estar vacío.';
    } else {
        try {
            $stmt = $pdo->prepare('UPDATE categorias SET nombre = ?, orden = ? WHERE id = ?');
            $stmt->execute([$nombre, $orden, $id]);
            redirigir('categorias.php?ok=1');
        } catch (PDOException $e) {
            $error = 'Ya existe otra categoría con ese nombre.';
        }
    }
    $categoria['nombre'] = $nombre;
    $categoria['orden'] = $orden;
}

require __DIR__ . '/includes/layout_header.php';
?>

<h1>Editar categoría</h1>

<?php if ($error): ?>
  <div class="aviso error"><?= e($error) ?></div>
<?php endif; ?>

<form method="post">
  <?= campoCsrf() ?>
  <div class="campo">
    <label for="nombre">Nombre</label>
    <input type="text" id="nombre" name="nombre" value="<?= e($categoria['nombre']) ?>" required>
  </div>
  <div class="campo">
    <label for="orden">Orden</label>
    <input type="number" id="orden" name="orden" value="<?= e((string)$categoria['orden']) ?>">
  </div>
  <button type="submit" class="btn">Guardar</button>
  <a href="categorias.php" class="btn secundario">Cancelar</a>
</form>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
