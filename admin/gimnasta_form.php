<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();

$pdo = getDb();
$seccionActual = 'gimnastas';

$id = (int)($_GET['id'] ?? 0);
$gimnasta = ['nombre' => '', 'categoria' => 'Infantil', 'modalidad' => 'Individual', 'aparato' => '', 'foto' => '', 'orden' => 0];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM gimnastas WHERE id = ?');
    $stmt->execute([$id]);
    $encontrada = $stmt->fetch();
    if ($encontrada) $gimnasta = $encontrada;
}

$tituloPagina = $id ? 'Editar gimnasta' : 'Nueva gimnasta';
$error = '';
$categorias = $pdo->query('SELECT nombre FROM categorias ORDER BY orden ASC')->fetchAll(PDO::FETCH_COLUMN);
$modalidades = ['Individual', 'Conjunto'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && subidaDemasiadoGrande()) {
    $error = 'La foto es demasiado grande para el límite de subida configurado en el servidor. Prueba con una imagen más ligera (o pide que se aumenten "upload_max_filesize" y "post_max_size" en la configuración de PHP del servidor).';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf();
    $gimnasta['nombre'] = trim($_POST['nombre'] ?? '');
    $gimnasta['categoria'] = $_POST['categoria'] ?? 'Infantil';
    $gimnasta['modalidad'] = $_POST['modalidad'] ?? 'Individual';
    $gimnasta['aparato'] = trim($_POST['aparato'] ?? '');
    $gimnasta['orden'] = (int)($_POST['orden'] ?? 0);

    $errorFoto = null;
    $nuevaFoto = procesarImagenSubida('foto', $errorFoto);
    if ($nuevaFoto) {
        $gimnasta['foto'] = $nuevaFoto;
    }

    if ($errorFoto) {
        $error = $errorFoto;
    } elseif ($gimnasta['nombre'] === '') {
        $error = 'El nombre es obligatorio.';
    } else {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE gimnastas SET nombre=?, categoria=?, modalidad=?, aparato=?, foto=?, orden=? WHERE id=?');
            $stmt->execute([$gimnasta['nombre'], $gimnasta['categoria'], $gimnasta['modalidad'], $gimnasta['aparato'], $gimnasta['foto'], $gimnasta['orden'], $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO gimnastas (nombre, categoria, modalidad, aparato, foto, orden) VALUES (?,?,?,?,?,?)');
            $stmt->execute([$gimnasta['nombre'], $gimnasta['categoria'], $gimnasta['modalidad'], $gimnasta['aparato'], $gimnasta['foto'], $gimnasta['orden']]);
        }
        redirigir('gimnastas.php?ok=1');
    }
}

require __DIR__ . '/includes/layout_header.php';
?>

<h1><?= $id ? 'Editar gimnasta' : 'Nueva gimnasta' ?></h1>

<?php if ($error): ?>
  <div class="aviso error"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <?= campoCsrf() ?>
  <div class="campo">
    <label for="nombre">Nombre (o nombre del conjunto)</label>
    <input type="text" id="nombre" name="nombre" value="<?= e($gimnasta['nombre']) ?>" required>
  </div>

  <div class="fila-2">
    <div class="campo">
      <label for="categoria">Categoría</label>
      <select id="categoria" name="categoria">
        <?php foreach ($categorias as $c): ?>
          <option value="<?= e($c) ?>" <?= $gimnasta['categoria'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="campo">
      <label for="modalidad">Modalidad</label>
      <select id="modalidad" name="modalidad">
        <?php foreach ($modalidades as $m): ?>
          <option value="<?= e($m) ?>" <?= $gimnasta['modalidad'] === $m ? 'selected' : '' ?>><?= e($m) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="fila-2">
    <div class="campo">
      <label for="aparato">Aparato principal</label>
      <input type="text" id="aparato" name="aparato" value="<?= e($gimnasta['aparato']) ?>" placeholder="Aro, pelota, mazas, cinta, cuerda...">
    </div>
    <div class="campo">
      <label for="foto">Foto (JPG, PNG o WEBP, máx. 6 MB)</label>
      <?php if (!empty($gimnasta['foto'])): ?>
        <div style="margin-bottom:8px;">
          <img src="../img/<?= e($gimnasta['foto']) ?>" alt="" style="max-width:120px;border-radius:8px;box-shadow:var(--sombra-chica);display:block;margin-bottom:6px;">
          <?php if ($id): ?>
            <a href="gimnasta_foto_borrar.php?id=<?= (int)$id ?>&csrf_token=<?= e(tokenCsrf()) ?>" class="borrar" style="font-size:13px;" onclick="return confirm('¿Quitar esta foto de la gimnasta?');">Quitar foto</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp">
    </div>
  </div>

  <div class="campo">
    <label for="orden">Orden en el listado</label>
    <input type="number" id="orden" name="orden" value="<?= e((string)$gimnasta['orden']) ?>">
  </div>

  <button type="submit" class="btn">Guardar gimnasta</button>
  <a href="gimnastas.php" class="btn secundario">Cancelar</a>
</form>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
