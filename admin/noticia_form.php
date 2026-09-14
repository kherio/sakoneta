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
    $error = 'Alguna foto es demasiado grande para el límite de subida configurado en el servidor. Prueba con imágenes más ligeras (o pide que se aumenten "upload_max_filesize" y "post_max_size" en la configuración de PHP del servidor).';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf();
    $noticia['titulo'] = trim($_POST['titulo'] ?? '');
    $noticia['resumen'] = trim($_POST['resumen'] ?? '');
    $noticia['contenido'] = trim($_POST['contenido'] ?? '');
    $noticia['fecha'] = $_POST['fecha'] ?? date('Y-m-d');
    $noticia['publicado'] = isset($_POST['publicado']) ? 1 : 0;

    if ($noticia['titulo'] === '' || $noticia['resumen'] === '' || $noticia['contenido'] === '') {
        $error = 'Título, resumen y contenido son obligatorios.';
    } else {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE noticias SET titulo=?, resumen=?, contenido=?, fecha=?, publicado=? WHERE id=?');
            $stmt->execute([$noticia['titulo'], $noticia['resumen'], $noticia['contenido'], $noticia['fecha'], $noticia['publicado'], $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO noticias (titulo, resumen, contenido, fecha, publicado) VALUES (?,?,?,?,?)');
            $stmt->execute([$noticia['titulo'], $noticia['resumen'], $noticia['contenido'], $noticia['fecha'], $noticia['publicado']]);
            $id = (int)$pdo->lastInsertId();
        }

        // Subir las fotos nuevas (se puede seleccionar más de una a la vez)
        $erroresFotos = [];
        $fotosNuevas = procesarImagenesMultiples('fotos', $erroresFotos);

        if ($fotosNuevas) {
            $maxOrden = (int)$pdo->query('SELECT COALESCE(MAX(orden), 0) m FROM noticia_fotos WHERE noticia_id = ' . (int)$id)->fetch()['m'];
            $stmtFoto = $pdo->prepare('INSERT INTO noticia_fotos (noticia_id, archivo, orden) VALUES (?, ?, ?)');
            foreach ($fotosNuevas as $i => $ruta) {
                $stmtFoto->execute([$id, $ruta, $maxOrden + $i + 1]);
            }
        }

        // Portada: la elegida entre las existentes, o la primera foto subida si no había ninguna
        $portadaElegida = trim($_POST['portada_existente'] ?? '');
        if ($portadaElegida !== '') {
            $pdo->prepare('UPDATE noticias SET imagen = ? WHERE id = ?')->execute([$portadaElegida, $id]);
        } elseif (empty($noticia['imagen']) && $fotosNuevas) {
            $pdo->prepare('UPDATE noticias SET imagen = ? WHERE id = ?')->execute([$fotosNuevas[0], $id]);
        }

        if ($erroresFotos) {
            $error = implode(' ', $erroresFotos);
        } else {
            redirigir('noticia_form.php?id=' . $id . '&ok=1');
        }
    }
}

$fotos = $id ? $pdo->prepare('SELECT * FROM noticia_fotos WHERE noticia_id = ? ORDER BY orden ASC') : null;
if ($fotos) { $fotos->execute([$id]); $fotos = $fotos->fetchAll(); } else { $fotos = []; }

if ($id) {
    $imagenActual = $pdo->prepare('SELECT imagen FROM noticias WHERE id = ?');
    $imagenActual->execute([$id]);
    $noticia['imagen'] = $imagenActual->fetchColumn();
}

require __DIR__ . '/includes/layout_header.php';
?>

<h1><?= $id ? 'Editar noticia' : 'Nueva noticia' ?></h1>

<?php if (isset($_GET['ok'])): ?>
  <div class="aviso ok">Cambios guardados correctamente.</div>
<?php endif; ?>
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

  <div class="campo">
    <label for="fecha">Fecha</label>
    <input type="date" id="fecha" name="fecha" value="<?= e($noticia['fecha']) ?>" required>
  </div>

  <div class="campo">
    <label><input type="checkbox" name="publicado" <?= $noticia['publicado'] ? 'checked' : '' ?> style="width:auto;"> Publicada (visible en la web)</label>
  </div>

  <hr style="border:none;border-top:1px solid var(--borde);margin:28px 0;">

  <h3 style="margin-top:0;">Fotos de la noticia</h3>
  <p style="color:var(--gris);font-size:14px;max-width:60ch;margin-top:-8px;">
    Puedes subir varias fotos para ilustrar bien la noticia. Marca cuál
    quieres usar como foto principal (aparece en los listados y en la
    cabecera de la noticia); el resto se muestran como galería al final.
  </p>

  <?php if ($fotos): ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:14px;margin-bottom:18px;">
      <?php foreach ($fotos as $f): ?>
        <div style="border:1.5px solid var(--borde);border-radius:8px;padding:8px;text-align:center;">
          <img src="../img/<?= e($f['archivo']) ?>" alt="" style="width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:6px;margin-bottom:6px;">
          <label style="font-size:12.5px;display:flex;align-items:center;gap:5px;justify-content:center;">
            <input type="radio" name="portada_existente" value="<?= e($f['archivo']) ?>" style="width:auto;" <?= $noticia['imagen'] === $f['archivo'] ? 'checked' : '' ?>>
            Usar como principal
          </label>
          <a href="noticia_foto_borrar.php?id=<?= (int)$f['id'] ?>&noticia_id=<?= (int)$id ?>&csrf_token=<?= e(tokenCsrf()) ?>" class="borrar" style="font-size:12px;display:block;margin-top:4px;" onclick="return confirm('¿Borrar esta foto?');">Borrar foto</a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php elseif ($id): ?>
    <p style="font-size:14px;color:var(--gris);">Todavía no has subido ninguna foto para esta noticia.</p>
  <?php endif; ?>

  <div class="campo">
    <label for="fotos">Añadir foto(s) nueva(s) (JPG, PNG o WEBP, máx. 20 MB cada una)</label>
    <input type="file" id="fotos" name="fotos[]" accept="image/jpeg,image/png,image/webp" multiple>
  </div>
  <?php if (!$id): ?>
    <p style="font-size:13px;color:var(--gris);margin-top:-10px;">Al guardar por primera vez, la primera foto que subas se usará como principal automáticamente. Podrás cambiarla después.</p>
  <?php endif; ?>

  <button type="submit" class="btn">Guardar noticia</button>
  <a href="noticias.php" class="btn secundario">Cancelar</a>
</form>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
