<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();
exigirRol(['administrador','editor']);

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
    $error = 'Alguna foto es demasiado grande para el límite de subida configurado en el servidor. Prueba con imágenes más ligeras (o pide que se aumenten "upload_max_filesize" y "post_max_size" en la configuración de PHP del servidor).';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf();
    $gimnasta['nombre'] = trim($_POST['nombre'] ?? '');
    $gimnasta['categoria'] = $_POST['categoria'] ?? 'Infantil';
    $gimnasta['modalidad'] = $_POST['modalidad'] ?? 'Individual';
    $gimnasta['aparato'] = trim($_POST['aparato'] ?? '');
    $gimnasta['orden'] = (int)($_POST['orden'] ?? 0);

    if ($gimnasta['nombre'] === '') {
        $error = 'El nombre es obligatorio.';
    } else {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE gimnastas SET nombre=?, categoria=?, modalidad=?, aparato=?, orden=? WHERE id=?');
            $stmt->execute([$gimnasta['nombre'], $gimnasta['categoria'], $gimnasta['modalidad'], $gimnasta['aparato'], $gimnasta['orden'], $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO gimnastas (nombre, categoria, modalidad, aparato, orden) VALUES (?,?,?,?,?)');
            $stmt->execute([$gimnasta['nombre'], $gimnasta['categoria'], $gimnasta['modalidad'], $gimnasta['aparato'], $gimnasta['orden']]);
            $id = (int)$pdo->lastInsertId();
        }

        $erroresFotos = [];
        $fotosNuevas = procesarImagenesMultiples('fotos', $erroresFotos);

        if ($fotosNuevas) {
            $maxOrden = (int)$pdo->query('SELECT COALESCE(MAX(orden), 0) m FROM gimnasta_fotos WHERE gimnasta_id = ' . (int)$id)->fetch()['m'];
            $stmtFoto = $pdo->prepare('INSERT INTO gimnasta_fotos (gimnasta_id, archivo, orden, tipo) VALUES (?, ?, ?, ?)');
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
            $comprobar = $pdo->prepare("SELECT COUNT(*) FROM gimnasta_fotos WHERE gimnasta_id = ? AND archivo = ? AND tipo = 'imagen'");
            $comprobar->execute([$id, $portadaElegida]);
            if ((int)$comprobar->fetchColumn() > 0) {
                $pdo->prepare('UPDATE gimnastas SET foto = ? WHERE id = ?')->execute([$portadaElegida, $id]);
            }
        } elseif (empty($gimnasta['foto']) && $primeraImagenNueva) {
            $pdo->prepare('UPDATE gimnastas SET foto = ? WHERE id = ?')->execute([$primeraImagenNueva, $id]);
        }

        if ($erroresFotos) {
            $error = implode(' ', $erroresFotos);
        } else {
            redirigir('gimnasta_form.php?id=' . $id . '&ok=1');
        }
    }
}

$fotos = $id ? $pdo->prepare('SELECT * FROM gimnasta_fotos WHERE gimnasta_id = ? ORDER BY orden ASC') : null;
if ($fotos) { $fotos->execute([$id]); $fotos = $fotos->fetchAll(); } else { $fotos = []; }

if ($id) {
    $fotoActual = $pdo->prepare('SELECT foto FROM gimnastas WHERE id = ?');
    $fotoActual->execute([$id]);
    $gimnasta['foto'] = $fotoActual->fetchColumn();
}

require __DIR__ . '/includes/layout_header.php';
?>

<h1><?= $id ? 'Editar gimnasta' : 'Nueva gimnasta' ?></h1>

<?php if (isset($_GET['ok'])): ?>
  <div class="aviso ok">Cambios guardados correctamente.</div>
<?php endif; ?>
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

  <div class="campo">
    <label for="aparato">Aparato principal</label>
    <input type="text" id="aparato" name="aparato" value="<?= e($gimnasta['aparato']) ?>" placeholder="Aro, pelota, mazas, cinta, cuerda...">
  </div>

  <div class="campo">
    <label for="orden">Orden en el listado</label>
    <input type="number" id="orden" name="orden" value="<?= e((string)$gimnasta['orden']) ?>">
  </div>

  <hr style="border:none;border-top:1px solid var(--borde);margin:28px 0;">

  <h3 style="margin-top:0;">Fotos y vídeos</h3>
  <p style="color:var(--gris);font-size:14px;max-width:60ch;margin-top:-8px;">
    Puedes subir varias fotos y vídeos. Marca cuál foto quieres usar
    como principal (los vídeos no se pueden usar como principal, pero
    sí se ven en la página de la gimnasta).
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
            <input type="radio" name="portada_existente" value="<?= e($f['archivo']) ?>" style="width:auto;" <?= $gimnasta['foto'] === $f['archivo'] ? 'checked' : '' ?>>
            Usar como principal
          </label>
          <?php else: ?>
          <p style="font-size:11.5px;color:var(--gris);margin:4px 0;">Vídeo</p>
          <?php endif; ?>
          <button type="submit" formaction="gimnasta_foto_borrar.php" name="id" value="<?= (int)$f['id'] ?>" class="borrar" style="font-size:12px;display:block;margin-top:4px;width:100%;" onclick="return confirm('¿Borrar este archivo?');">Borrar</button>
        </div>
      <?php endforeach; ?>
    </div>
  <?php elseif ($id): ?>
    <p style="font-size:14px;color:var(--gris);">Todavía no has subido ninguna foto ni vídeo para esta gimnasta.</p>
  <?php endif; ?>

  <div class="campo">
    <label for="fotos">Añadir foto(s) o vídeo(s) nuevos (JPG, PNG, WEBP hasta 20 MB; MP4, WEBM o MOV hasta 80 MB)</label>
    <input type="file" id="fotos" name="fotos[]" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime" multiple>
  </div>
  <?php if (!$id): ?>
    <p style="font-size:13px;color:var(--gris);margin-top:-10px;">Al guardar por primera vez, la primera foto que subas se usará como principal automáticamente. Podrás cambiarla después.</p>
  <?php endif; ?>

  <button type="submit" class="btn">Guardar gimnasta</button>
  <a href="gimnastas.php" class="btn secundario">Cancelar</a>
</form>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
