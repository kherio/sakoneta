<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();
exigirRol(['administrador','editor']);

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && subidaDemasiadoGrande()) {
    $error = 'Alguna foto es demasiado grande para el límite de subida configurado en el servidor. Prueba con imágenes más ligeras (o pide que se aumenten "upload_max_filesize" y "post_max_size" en la configuración de PHP del servidor).';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf();
    $nombre = trim($_POST['nombre'] ?? '');
    $orden = (int)($_POST['orden'] ?? 0);

    if ($nombre === '') {
        $error = 'El nombre no puede estar vacío.';
    } else {
        try {
            $stmt = $pdo->prepare('UPDATE categorias SET nombre = ?, orden = ? WHERE id = ?');
            $stmt->execute([$nombre, $orden, $id]);

            $erroresFotos = [];
            $fotosNuevas = procesarImagenesMultiples('fotos', $erroresFotos);

            if ($fotosNuevas) {
                $maxOrden = (int)$pdo->query('SELECT COALESCE(MAX(orden), 0) m FROM categoria_fotos WHERE categoria_id = ' . (int)$id)->fetch()['m'];
                $stmtFoto = $pdo->prepare('INSERT INTO categoria_fotos (categoria_id, archivo, orden, tipo) VALUES (?, ?, ?, ?)');
                foreach ($fotosNuevas as $i => $media) {
                    $stmtFoto->execute([$id, $media['archivo'], $maxOrden + $i + 1, $media['tipo']]);
                }
            }

            $primeraImagenNueva = null;
            foreach ($fotosNuevas as $media) {
                if ($media['tipo'] === 'imagen') { $primeraImagenNueva = $media['archivo']; break; }
            }
            $portadaElegida = trim($_POST['portada_existente'] ?? '');
            if ($portadaElegida !== '') {
                $comprobar = $pdo->prepare("SELECT COUNT(*) FROM categoria_fotos WHERE categoria_id = ? AND archivo = ? AND tipo = 'imagen'");
                $comprobar->execute([$id, $portadaElegida]);
                if ((int)$comprobar->fetchColumn() > 0) {
                    $pdo->prepare('UPDATE categorias SET imagen_portada = ? WHERE id = ?')->execute([$portadaElegida, $id]);
                }
            } elseif (empty($categoria['imagen_portada']) && $primeraImagenNueva) {
                $pdo->prepare('UPDATE categorias SET imagen_portada = ? WHERE id = ?')->execute([$primeraImagenNueva, $id]);
            }

            if ($erroresFotos) {
                $error = implode(' ', $erroresFotos);
            } else {
                redirigir('categorias.php?ok=1');
            }
        } catch (PDOException $e) {
            $error = 'Ya existe otra categoría con ese nombre.';
        }
    }
    $categoria['nombre'] = $nombre;
    $categoria['orden'] = $orden;
}

$fotos = $pdo->prepare('SELECT * FROM categoria_fotos WHERE categoria_id = ? ORDER BY orden ASC');
$fotos->execute([$id]);
$fotos = $fotos->fetchAll();

$portadaActual = $pdo->prepare('SELECT imagen_portada FROM categorias WHERE id = ?');
$portadaActual->execute([$id]);
$categoria['imagen_portada'] = $portadaActual->fetchColumn();

require __DIR__ . '/includes/layout_header.php';
?>

<h1>Editar categoría</h1>

<?php if (isset($_GET['ok'])): ?>
  <div class="aviso ok">Cambios guardados correctamente.</div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="aviso error"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <?= campoCsrf() ?>
  <div class="campo">
    <label for="nombre">Nombre</label>
    <input type="text" id="nombre" name="nombre" value="<?= e($categoria['nombre']) ?>" required>
  </div>
  <div class="campo">
    <label for="orden">Orden</label>
    <input type="number" id="orden" name="orden" value="<?= e((string)$categoria['orden']) ?>">
  </div>

  <hr style="border:none;border-top:1px solid var(--borde);margin:28px 0;">

  <h3 style="margin-top:0;">Fotos y vídeos de la categoría</h3>
  <p style="color:var(--gris);font-size:14px;max-width:60ch;margin-top:-8px;">
    Aparecen en la página pública de esta categoría
    (<a href="../categoria.php?nombre=<?= urlencode($categoria['nombre']) ?>" target="_blank">verla</a>).
    Los vídeos no se pueden usar como portada.
  </p>

  <?php if ($fotos): ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:14px;margin-bottom:18px;">
      <?php foreach ($fotos as $f): ?>
        <div style="border:1.5px solid var(--borde);border-radius:8px;padding:8px;text-align:center;">
          <?php if ($f['tipo'] === 'video'): ?>
            <video src="../img/<?= e($f['archivo']) ?>" controls style="width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:6px;margin-bottom:6px;background:#000;"></video>
          <?php else: ?>
            <img src="../img/<?= e($f['archivo']) ?>" alt="" style="width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:6px;margin-bottom:6px;">
          <?php endif; ?>
          <?php if ($f['tipo'] === 'imagen'): ?>
          <label style="font-size:12.5px;display:flex;align-items:center;gap:5px;justify-content:center;">
            <input type="radio" name="portada_existente" value="<?= e($f['archivo']) ?>" style="width:auto;" <?= $categoria['imagen_portada'] === $f['archivo'] ? 'checked' : '' ?>>
            Usar como portada
          </label>
          <?php else: ?>
          <p style="font-size:11.5px;color:var(--gris);margin:4px 0;">Vídeo</p>
          <?php endif; ?>
          <button type="submit" formaction="categoria_foto_borrar.php" name="id" value="<?= (int)$f['id'] ?>" class="borrar" style="font-size:12px;display:block;margin-top:4px;width:100%;" onclick="return confirm('¿Borrar este archivo?');">Borrar</button>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p style="font-size:14px;color:var(--gris);">Todavía no has subido ninguna foto ni vídeo para esta categoría.</p>
  <?php endif; ?>

  <div class="campo">
    <label for="fotos">Añadir foto(s) o vídeo(s) nuevos (JPG, PNG, WEBP hasta 20 MB; MP4, WEBM o MOV hasta 80 MB)</label>
    <input type="file" id="fotos" name="fotos[]" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime" multiple>
  </div>

  <button type="submit" class="btn">Guardar</button>
  <a href="categorias.php" class="btn secundario">Cancelar</a>
</form>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
