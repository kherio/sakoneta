<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();

$pdo = getDb();
$seccionActual = 'noticias';

$id = (int)($_GET['id'] ?? 0);
$noticia = ['titulo' => '', 'resumen' => '', 'contenido' => '', 'imagen' => '', 'fecha' => date('Y-m-d'), 'publicado' => 1];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM noticias WHERE id = ?');
    $stmt->execute([$id]);
    $encontrada = $stmt->fetch();
    if ($encontrada) $noticia = $encontrada;
}

$tituloPagina = $id ? 'Editar noticia' : 'Nueva noticia';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && subidaDemasiadoGrande()) {
    $error = 'La imagen es demasiado grande para el límite de subida configurado en el servidor. Prueba con una imagen más ligera (o pide que se aumenten "upload_max_filesize" y "post_max_size" en la configuración de PHP del servidor).';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf();
    $noticia['titulo'] = trim($_POST['titulo'] ?? '');
    $noticia['resumen'] = trim($_POST['resumen'] ?? '');
    $noticia['contenido'] = trim($_POST['contenido'] ?? '');
    $noticia['fecha'] = $_POST['fecha'] ?? date('Y-m-d');
    $noticia['publicado'] = isset($_POST['publicado']) ? 1 : 0;

    $errorImagen = null;
    $nuevaImagen = procesarImagenSubida('imagen', $errorImagen);
    if ($nuevaImagen) {
        $noticia['imagen'] = $nuevaImagen;
    }

    if ($errorImagen) {
        $error = $errorImagen;
    } elseif ($noticia['titulo'] === '' || $noticia['resumen'] === '' || $noticia['contenido'] === '') {
        $error = 'Título, resumen y contenido son obligatorios.';
    } else {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE noticias SET titulo=?, resumen=?, contenido=?, imagen=?, fecha=?, publicado=? WHERE id=?');
            $stmt->execute([$noticia['titulo'], $noticia['resumen'], $noticia['contenido'], $noticia['imagen'], $noticia['fecha'], $noticia['publicado'], $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO noticias (titulo, resumen, contenido, imagen, fecha, publicado) VALUES (?,?,?,?,?,?)');
            $stmt->execute([$noticia['titulo'], $noticia['resumen'], $noticia['contenido'], $noticia['imagen'], $noticia['fecha'], $noticia['publicado']]);
        }
        redirigir('noticias.php?ok=1');
    }
}

require __DIR__ . '/includes/layout_header.php';
?>

<h1><?= $id ? 'Editar noticia' : 'Nueva noticia' ?></h1>

<?php if ($error): ?>
  <div class="aviso error"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <?= campoCsrf() ?>
  <div class="campo">
    <label for="titulo">Título</label>
    <input type="text" id="titulo" name="titulo" value="<?= e($noticia['titulo']) ?>" required>
  </div>

  <div class="campo">
    <label for="resumen">Resumen (aparece en los listados)</label>
    <input type="text" id="resumen" name="resumen" value="<?= e($noticia['resumen']) ?>" required>
  </div>

  <div class="campo">
    <label for="contenido">Contenido (separa los párrafos con una línea en blanco)</label>
    <textarea id="contenido" name="contenido" required><?= e($noticia['contenido']) ?></textarea>
  </div>

  <div class="fila-2">
    <div class="campo">
      <label for="imagen">Imagen (JPG, PNG o WEBP, máx. 10 MB)</label>
      <?php if (!empty($noticia['imagen'])): ?>
        <div style="margin-bottom:8px;">
          <img src="../img/<?= e($noticia['imagen']) ?>" alt="" style="max-width:160px;border-radius:8px;box-shadow:var(--sombra-chica);display:block;margin-bottom:6px;">
          <?php if ($id): ?>
            <a href="noticia_imagen_borrar.php?id=<?= (int)$id ?>&csrf_token=<?= e(tokenCsrf()) ?>" class="borrar" style="font-size:13px;" onclick="return confirm('¿Quitar esta imagen de la noticia?');">Quitar imagen</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/webp">
    </div>
    <div class="campo">
      <label for="fecha">Fecha</label>
      <input type="date" id="fecha" name="fecha" value="<?= e($noticia['fecha']) ?>" required>
    </div>
  </div>

  <div class="campo">
    <label><input type="checkbox" name="publicado" <?= $noticia['publicado'] ? 'checked' : '' ?> style="width:auto;"> Publicada (visible en la web)</label>
  </div>

  <button type="submit" class="btn">Guardar noticia</button>
  <a href="noticias.php" class="btn secundario">Cancelar</a>
</form>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
