<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
exigirAutenticacion();
exigirRol(['administrador','editor']);

$pdo = getDb();
$seccionActual = 'competiciones';

$id = (int)($_GET['id'] ?? 0);
$competicion = ['nombre' => '', 'categoria' => 'Infantil', 'lugar' => '', 'fecha' => date('Y-m-d'), 'resultado' => '', 'disputada' => 0, 'imagen_portada' => null, 'descripcion' => ''];

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
    $competicion['descripcion'] = trim($_POST['descripcion'] ?? '');

    if ($competicion['nombre'] === '' || $competicion['lugar'] === '') {
        $error = 'El nombre y el lugar son obligatorios.';
    } else {
        // Guardar los datos básicos primero (crea el id si es una competición nueva)
        if ($id) {
            $stmt = $pdo->prepare('UPDATE competiciones SET nombre=?, categoria=?, lugar=?, fecha=?, resultado=?, disputada=?, descripcion=? WHERE id=?');
            $stmt->execute([$competicion['nombre'], $competicion['categoria'], $competicion['lugar'], $competicion['fecha'], $competicion['resultado'], $competicion['disputada'], $competicion['descripcion'] ?: null, $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO competiciones (nombre, categoria, lugar, fecha, resultado, disputada, descripcion) VALUES (?,?,?,?,?,?,?)');
            $stmt->execute([$competicion['nombre'], $competicion['categoria'], $competicion['lugar'], $competicion['fecha'], $competicion['resultado'], $competicion['disputada'], $competicion['descripcion'] ?: null]);
            $id = (int)$pdo->lastInsertId();
        }

        // Subir las fotos y vídeos nuevos (se puede seleccionar varios a la vez)
        $erroresFotos = [];
        $fotosNuevas = procesarImagenesMultiples('fotos', $erroresFotos);

        // Añadir también las fotos elegidas de la biblioteca (ya subidas
        // antes desde cualquier otra parte del sitio)
        $seleccionBiblioteca = $_POST['fotos_biblioteca'] ?? [];
        $fotosDeBiblioteca = [];
        if (is_array($seleccionBiblioteca)) {
            $mediaDisponible = array_column(listarMediaSubida(), null, 'archivo');
            foreach ($seleccionBiblioteca as $archivoElegido) {
                if (isset($mediaDisponible[$archivoElegido])) {
                    $fotosDeBiblioteca[] = $mediaDisponible[$archivoElegido];
                }
            }
        }
        $fotosAAgregar = array_merge($fotosNuevas, $fotosDeBiblioteca);

        if ($fotosAAgregar) {
            $maxOrden = (int)$pdo->query('SELECT COALESCE(MAX(orden), 0) m FROM competicion_fotos WHERE competicion_id = ' . (int)$id)->fetch()['m'];
            $stmtFoto = $pdo->prepare('INSERT INTO competicion_fotos (competicion_id, archivo, orden, tipo) VALUES (?, ?, ?, ?)');
            foreach ($fotosAAgregar as $i => $media) {
                $stmtFoto->execute([$id, $media['archivo'], $maxOrden + $i + 1, $media['tipo']]);
            }
        }

        // Determinar la foto de portada (solo puede ser una imagen): la
        // elegida entre las existentes, o si no había ninguna todavía, la
        // primera imagen añadida en este envío.
        $primeraImagenNueva = null;
        foreach ($fotosAAgregar as $media) {
            if ($media['tipo'] === 'imagen') { $primeraImagenNueva = $media['archivo']; break; }
        }
        $portadaElegida = trim($_POST['portada_existente'] ?? '');
        if ($portadaElegida !== '') {
            $comprobar = $pdo->prepare("SELECT COUNT(*) FROM competicion_fotos WHERE competicion_id = ? AND archivo = ? AND tipo = 'imagen'");
            $comprobar->execute([$id, $portadaElegida]);
            if ((int)$comprobar->fetchColumn() > 0) {
                $pdo->prepare('UPDATE competiciones SET imagen_portada = ? WHERE id = ?')->execute([$portadaElegida, $id]);
            }
        } elseif (empty($competicion['imagen_portada']) && $primeraImagenNueva) {
            $pdo->prepare('UPDATE competiciones SET imagen_portada = ? WHERE id = ?')->execute([$primeraImagenNueva, $id]);
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

$archivosYaEnGaleria = array_column($fotos, 'archivo');
$mediaBiblioteca = array_filter(listarMediaSubida(), fn($m) => !in_array($m['archivo'], $archivosYaEnGaleria, true));

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

  <div class="campo">
    <label for="descripcion">Información sobre el campeonato (opcional)</label>
    <textarea id="descripcion" name="descripcion" placeholder="Cuenta cómo fue la jornada, cómo se preparó el equipo, anécdotas... Separa los párrafos con una línea en blanco."><?= e($competicion['descripcion'] ?? '') ?></textarea>
  </div>

  <hr style="border:none;border-top:1px solid var(--borde);margin:28px 0;">

  <h3 style="margin-top:0;">Fotos y vídeos de la competición</h3>
  <p style="color:var(--gris);font-size:14px;max-width:60ch;margin-top:-8px;">
    Puedes subir varias fotos y vídeos a la vez. Marca qué foto quieres
    usar como foto de fondo/portada de esta competición en la web (los
    vídeos no se pueden usar como portada, pero sí se ven en su galería).
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
            <input type="radio" name="portada_existente" value="<?= e($f['archivo']) ?>" style="width:auto;" <?= $competicion['imagen_portada'] === $f['archivo'] ? 'checked' : '' ?>>
            Usar como portada
          </label>
          <?php else: ?>
          <p style="font-size:11.5px;color:var(--gris);margin:4px 0;">Vídeo</p>
          <?php endif; ?>
          <button type="submit" formaction="competicion_foto_borrar.php" name="id" value="<?= (int)$f['id'] ?>" class="borrar" style="font-size:12px;display:block;margin-top:4px;width:100%;" onclick="return confirm('¿Borrar este archivo?');">Borrar</button>
        </div>
      <?php endforeach; ?>
    </div>
  <?php elseif ($id): ?>
    <p style="font-size:14px;color:var(--gris);">Todavía no has subido ninguna foto ni vídeo para esta competición.</p>
  <?php endif; ?>

  <div class="campo">
    <label for="fotos">Añadir foto(s) o vídeo(s) nuevos (JPG, PNG, WEBP hasta 20 MB; MP4, WEBM o MOV hasta 80 MB)</label>
    <input type="file" id="fotos" name="fotos[]" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime" multiple>
  </div>
  <?php if (!$id): ?>
    <p style="font-size:13px;color:var(--gris);margin-top:-10px;">Al guardar por primera vez, la primera foto que subas se usará como portada automáticamente. Podrás cambiarla después.</p>
  <?php endif; ?>

  <?php if ($mediaBiblioteca): ?>
  <details style="margin-bottom:18px;">
    <summary style="cursor:pointer;font-weight:600;font-size:14.5px;color:var(--morado);">O elige entre las fotos y vídeos ya subidos antes (<?= count($mediaBiblioteca) ?>)</summary>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:10px;margin-top:12px;max-height:340px;overflow-y:auto;padding:4px;">
      <?php foreach ($mediaBiblioteca as $m): ?>
        <label style="cursor:pointer;border:1.5px solid var(--borde);border-radius:8px;padding:5px;text-align:center;display:block;">
          <input type="checkbox" name="fotos_biblioteca[]" value="<?= e($m['archivo']) ?>" style="width:auto;margin-bottom:4px;">
          <?php if ($m['tipo'] === 'video'): ?>
            <video src="../img/<?= e($m['archivo']) ?>" style="width:100%;aspect-ratio:1/1;object-fit:cover;border-radius:5px;display:block;" muted></video>
          <?php else: ?>
            <img src="../img/<?= e($m['archivo']) ?>" alt="" style="width:100%;aspect-ratio:1/1;object-fit:cover;border-radius:5px;display:block;">
          <?php endif; ?>
        </label>
      <?php endforeach; ?>
    </div>
  </details>
  <?php endif; ?>

  <button type="submit" class="btn">Guardar competición</button>
  <a href="competiciones.php" class="btn secundario">Cancelar</a>
</form>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
