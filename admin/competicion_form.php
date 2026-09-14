<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();

$pdo = getDb();
$seccionActual = 'competiciones';

$id = (int)($_GET['id'] ?? 0);
$competicion = ['nombre' => '', 'categoria' => 'Infantil', 'lugar' => '', 'fecha' => date('Y-m-d'), 'resultado' => '', 'disputada' => 0, 'imagen_portada' => null];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM competiciones WHERE id = ?');
    $stmt->execute([$id]);
    $encontrada = $stmt->fetch();
    if ($encontrada) $competicion = $encontrada;
}

$tituloPagina = $id ? 'Editar competición' : 'Nueva competición';
$error = '';
$categorias = $pdo->query('SELECT nombre FROM categorias ORDER BY orden ASC')->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && subidaDemasiadoGrande()) {
    $error = 'Alguna foto es demasiado grande para el límite de subida configurado en el servidor. Prueba con imágenes más ligeras (o pide que se aumenten "upload_max_filesize" y "post_max_size" en la configuración de PHP del servidor).';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf();
    $competicion['nombre'] = trim($_POST['nombre'] ?? '');
    $competicion['categoria'] = $_POST['categoria'] ?? 'Infantil';
    $competicion['lugar'] = trim($_POST['lugar'] ?? '');
    $competicion['fecha'] = $_POST['fecha'] ?? date('Y-m-d');
    $competicion['disputada'] = isset($_POST['disputada']) ? 1 : 0;
    $competicion['resultado'] = $competicion['disputada'] ? trim($_POST['resultado'] ?? '') : null;

    if ($competicion['nombre'] === '' || $competicion['lugar'] === '') {
        $error = 'El nombre y el lugar son obligatorios.';
    } else {
        // Guardar los datos básicos primero (crea el id si es una competición nueva)
        if ($id) {
            $stmt = $pdo->prepare('UPDATE competiciones SET nombre=?, categoria=?, lugar=?, fecha=?, resultado=?, disputada=? WHERE id=?');
            $stmt->execute([$competicion['nombre'], $competicion['categoria'], $competicion['lugar'], $competicion['fecha'], $competicion['resultado'], $competicion['disputada'], $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO competiciones (nombre, categoria, lugar, fecha, resultado, disputada) VALUES (?,?,?,?,?,?)');
            $stmt->execute([$competicion['nombre'], $competicion['categoria'], $competicion['lugar'], $competicion['fecha'], $competicion['resultado'], $competicion['disputada']]);
            $id = (int)$pdo->lastInsertId();
        }

        // Subir las fotos nuevas (se puede seleccionar más de una a la vez)
        $erroresFotos = [];
        $fotosNuevas = procesarImagenesMultiples('fotos', $erroresFotos);

        if ($fotosNuevas) {
            $maxOrden = (int)$pdo->query('SELECT COALESCE(MAX(orden), 0) m FROM competicion_fotos WHERE competicion_id = ' . (int)$id)->fetch()['m'];
            $stmtFoto = $pdo->prepare('INSERT INTO competicion_fotos (competicion_id, archivo, orden) VALUES (?, ?, ?)');
            foreach ($fotosNuevas as $i => $ruta) {
                $stmtFoto->execute([$id, $ruta, $maxOrden + $i + 1]);
            }
        }

        // Determinar la foto de portada: la elegida entre las existentes,
        // o si no había ninguna todavía, la primera foto subida en este envío.
        $portadaElegida = trim($_POST['portada_existente'] ?? '');
        if ($portadaElegida !== '') {
            $pdo->prepare('UPDATE competiciones SET imagen_portada = ? WHERE id = ?')->execute([$portadaElegida, $id]);
        } elseif (empty($competicion['imagen_portada']) && $fotosNuevas) {
            $pdo->prepare('UPDATE competiciones SET imagen_portada = ? WHERE id = ?')->execute([$fotosNuevas[0], $id]);
        }

        if ($erroresFotos) {
            $error = implode(' ', $erroresFotos);
        } else {
            redirigir('competicion_form.php?id=' . $id . '&ok=1');
        }
    }
}

$fotos = $id ? $pdo->prepare('SELECT * FROM competicion_fotos WHERE competicion_id = ? ORDER BY orden ASC') : null;
if ($fotos) { $fotos->execute([$id]); $fotos = $fotos->fetchAll(); } else { $fotos = []; }

// Releer la portada actual por si se acaba de actualizar en este envío
if ($id) {
    $portadaActual = $pdo->prepare('SELECT imagen_portada FROM competiciones WHERE id = ?');
    $portadaActual->execute([$id]);
    $competicion['imagen_portada'] = $portadaActual->fetchColumn();
}

require __DIR__ . '/includes/layout_header.php';
?>

<h1><?= $id ? 'Editar competición' : 'Nueva competición' ?></h1>

<?php if (isset($_GET['ok'])): ?>
  <div class="aviso ok">Cambios guardados correctamente.</div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="aviso error"><?= e($error) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <?= campoCsrf() ?>
  <div class="fila-2">
    <div class="campo">
      <label for="nombre">Nombre de la competición</label>
      <input type="text" id="nombre" name="nombre" value="<?= e($competicion['nombre']) ?>" required>
    </div>
    <div class="campo">
      <label for="categoria">Categoría</label>
      <select id="categoria" name="categoria">
        <?php foreach ($categorias as $c): ?>
          <option value="<?= e($c) ?>" <?= $competicion['categoria'] === $c ? 'selected' : '' ?>><?= e($c) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="fila-2">
    <div class="campo">
      <label for="lugar">Lugar</label>
      <input type="text" id="lugar" name="lugar" value="<?= e($competicion['lugar']) ?>" required>
    </div>
    <div class="campo">
      <label for="fecha">Fecha</label>
      <input type="date" id="fecha" name="fecha" value="<?= e($competicion['fecha']) ?>" required>
    </div>
  </div>

  <div class="campo">
    <label><input type="checkbox" name="disputada" id="disputada" <?= $competicion['disputada'] ? 'checked' : '' ?> style="width:auto;"> Ya disputada (permite indicar el resultado)</label>
  </div>

  <div class="campo">
    <label for="resultado">Resultado</label>
    <input type="text" id="resultado" name="resultado" value="<?= e($competicion['resultado'] ?? '') ?>" placeholder="Ej: Oro en conjunto, 4ª individual">
  </div>

  <hr style="border:none;border-top:1px solid var(--borde);margin:28px 0;">

  <h3 style="margin-top:0;">Fotos de la competición</h3>
  <p style="color:var(--gris);font-size:14px;max-width:60ch;margin-top:-8px;">
    Puedes subir varias fotos a la vez. Marca cuál quieres usar como foto de
    fondo/portada de esta competición en la web.
  </p>

  <?php if ($fotos): ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:14px;margin-bottom:18px;">
      <?php foreach ($fotos as $f): ?>
        <div style="border:1.5px solid var(--borde);border-radius:8px;padding:8px;text-align:center;">
          <img src="../img/<?= e($f['archivo']) ?>" alt="" style="width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:6px;margin-bottom:6px;">
          <label style="font-size:12.5px;display:flex;align-items:center;gap:5px;justify-content:center;">
            <input type="radio" name="portada_existente" value="<?= e($f['archivo']) ?>" style="width:auto;" <?= $competicion['imagen_portada'] === $f['archivo'] ? 'checked' : '' ?>>
            Usar como portada
          </label>
          <a href="competicion_foto_borrar.php?id=<?= (int)$f['id'] ?>&competicion_id=<?= (int)$id ?>&csrf_token=<?= e(tokenCsrf()) ?>" class="borrar" style="font-size:12px;display:block;margin-top:4px;" onclick="return confirm('¿Borrar esta foto?');">Borrar foto</a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php elseif ($id): ?>
    <p style="font-size:14px;color:var(--gris);">Todavía no has subido ninguna foto para esta competición.</p>
  <?php endif; ?>

  <div class="campo">
    <label for="fotos">Añadir foto(s) nueva(s) (JPG, PNG o WEBP, máx. 20 MB cada una)</label>
    <input type="file" id="fotos" name="fotos[]" accept="image/jpeg,image/png,image/webp" multiple>
  </div>
  <?php if (!$id): ?>
    <p style="font-size:13px;color:var(--gris);margin-top:-10px;">Al guardar por primera vez, la primera foto que subas se usará como portada automáticamente. Podrás cambiarla después.</p>
  <?php endif; ?>

  <button type="submit" class="btn">Guardar competición</button>
  <a href="competiciones.php" class="btn secundario">Cancelar</a>
</form>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
