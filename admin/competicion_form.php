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

$categoriasElegidas = $id
    ? $pdo->query('SELECT categoria FROM competicion_categorias WHERE competicion_id = ' . (int)$id)->fetchAll(PDO::FETCH_COLUMN)
    : [$competicion['categoria']];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && subidaDemasiadoGrande()) {
    $error = 'Alguna foto es demasiado grande para el límite de subida configurado en el servidor. Prueba con imágenes más ligeras (o pide que se aumenten "upload_max_filesize" y "post_max_size" en la configuración de PHP del servidor).';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirCsrf();
    $competicion['nombre'] = trim($_POST['nombre'] ?? '');
    $categoriasElegidas = array_values(array_intersect((array)($_POST['categorias'] ?? []), $categorias));
    if (!$categoriasElegidas) {
        $categoriasElegidas = [$categorias[0] ?? 'Infantil'];
    }
    $competicion['categoria'] = $categoriasElegidas[0];
    $competicion['lugar'] = trim($_POST['lugar'] ?? '');
    $competicion['fecha'] = $_POST['fecha'] ?? date('Y-m-d');
    $competicion['hora'] = preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', trim($_POST['hora'] ?? '')) ? trim($_POST['hora']) : null;
    $competicion['disputada'] = isset($_POST['disputada']) ? 1 : 0;
    $competicion['resultado'] = $competicion['disputada'] ? trim($_POST['resultado'] ?? '') : null;
    $competicion['descripcion'] = trim($_POST['descripcion'] ?? '');
    if (preg_match('/^(\d{1,3})\s+(\d{1,3})$/', trim($_POST['imagen_posicion'] ?? ''), $m) && (int)$m[1] <= 100 && (int)$m[2] <= 100) {
        $competicion['imagen_posicion'] = (int)$m[1] . ' ' . (int)$m[2];
    } else {
        $competicion['imagen_posicion'] = $competicion['imagen_posicion'] ?? '50 12';
    }

    if ($competicion['nombre'] === '' || $competicion['lugar'] === '') {
        $error = 'El nombre y el lugar son obligatorios.';
    } else {
        // Guardar los datos básicos primero (crea el id si es una competición nueva)
        if ($id) {
            $stmt = $pdo->prepare('UPDATE competiciones SET nombre=?, categoria=?, lugar=?, fecha=?, hora=?, resultado=?, disputada=?, descripcion=?, imagen_posicion=? WHERE id=?');
            $stmt->execute([$competicion['nombre'], $competicion['categoria'], $competicion['lugar'], $competicion['fecha'], $competicion['hora'], $competicion['resultado'], $competicion['disputada'], $competicion['descripcion'] ?: null, $competicion['imagen_posicion'], $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO competiciones (nombre, categoria, lugar, fecha, hora, resultado, disputada, descripcion, imagen_posicion) VALUES (?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$competicion['nombre'], $competicion['categoria'], $competicion['lugar'], $competicion['fecha'], $competicion['hora'], $competicion['resultado'], $competicion['disputada'], $competicion['descripcion'] ?: null, $competicion['imagen_posicion']]);
            $id = (int)$pdo->lastInsertId();
        }

        // Guardar el conjunto completo de categorías elegidas
        $pdo->prepare('DELETE FROM competicion_categorias WHERE competicion_id = ?')->execute([$id]);
        $stmtCat = $pdo->prepare('INSERT OR IGNORE INTO competicion_categorias (competicion_id, categoria) VALUES (?, ?)');
        foreach ($categoriasElegidas as $cat) {
            $stmtCat->execute([$id, $cat]);
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

        // Documentos (PDF, DOCX): convocatoria, resultados oficiales...
        $erroresDocumentos = [];
        $documentosNuevos = procesarDocumentosMultiples('documentos', $erroresDocumentos);
        if ($documentosNuevos) {
            $maxOrdenDoc = (int)$pdo->query('SELECT COALESCE(MAX(orden), 0) m FROM competicion_documentos WHERE competicion_id = ' . (int)$id)->fetch()['m'];
            $stmtDoc = $pdo->prepare('INSERT INTO competicion_documentos (competicion_id, archivo, nombre_original, orden) VALUES (?, ?, ?, ?)');
            foreach ($documentosNuevos as $i => $doc) {
                $stmtDoc->execute([$id, $doc['archivo'], $doc['nombre_original'], $maxOrdenDoc + $i + 1]);
            }
        }
        $erroresFotos = array_merge($erroresFotos, $erroresDocumentos);

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

$documentos = $id ? $pdo->prepare('SELECT * FROM competicion_documentos WHERE competicion_id = ? ORDER BY orden ASC') : null;
if ($documentos) { $documentos->execute([$id]); $documentos = $documentos->fetchAll(); } else { $documentos = []; }

$minutajeGuardado = $id ? $pdo->prepare('SELECT * FROM competicion_minutaje WHERE competicion_id = ? ORDER BY hora ASC, orden ASC') : null;
if ($minutajeGuardado) { $minutajeGuardado->execute([$id]); $minutajeGuardado = $minutajeGuardado->fetchAll(); } else { $minutajeGuardado = []; }
$minutajeBorrador = $_SESSION['minutaje_borrador'][$id] ?? [];
$errorMinutaje = leerFlash('minutaje_error');

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
      <label>Categorías</label>
      <div style="display:flex;flex-wrap:wrap;gap:6px 16px;padding:10px 0;">
        <?php foreach ($categorias as $c): ?>
          <label style="display:flex;align-items:center;gap:5px;font-weight:400;font-size:14px;width:auto;">
            <input type="checkbox" name="categorias[]" value="<?= e($c) ?>" style="width:auto;" <?= in_array($c, $categoriasElegidas, true) ? 'checked' : '' ?>>
            <?= e($c) ?>
          </label>
        <?php endforeach; ?>
      </div>
      <p style="font-size:12.5px;color:var(--gris);margin-top:-4px;">Marca todas las categorías que participen en esta competición (puede ser más de una).</p>
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
    <div class="campo">
      <label for="hora">Hora de inicio (opcional)</label>
      <input type="time" id="hora" name="hora" value="<?= e($competicion['hora'] ?? '') ?>">
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

  <div class="campo">
    <label>Encuadre de la foto principal (en la cabecera de la competición)</label>
    <?php if (!empty($competicion['imagen_portada'])): [$posX, $posY] = posicionXY($competicion['imagen_posicion'] ?? null); ?>
      <div class="selector-encuadre" id="selector-encuadre">
        <img src="../img/<?= e($competicion['imagen_portada']) ?>" alt="">
        <div class="selector-encuadre-rejilla"></div>
        <div class="selector-encuadre-marca" id="marca-encuadre" style="left:<?= $posX ?>%;top:<?= $posY ?>%;"></div>
      </div>
      <input type="hidden" name="imagen_posicion" id="imagen_posicion_input" value="<?= $posX ?> <?= $posY ?>">
      <p style="font-size:12.5px;color:var(--gris);margin-top:6px;">Haz clic o arrastra sobre la foto para marcar qué parte quieres que se vea en la cabecera (que es bastante alta).</p>
    <?php else: ?>
      <input type="hidden" name="imagen_posicion" value="50 12">
      <p style="font-size:13px;color:var(--gris);">Guarda primero una foto de portada; después podrás ajustar aquí mismo qué parte se ve en la cabecera.</p>
    <?php endif; ?>
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
          <button type="submit" formaction="competicion_foto_borrar.php" name="id" value="<?= (int)$f['id'] ?>" class="borrar borrar-bloque" onclick="return confirm('¿Borrar este archivo?');">Borrar</button>
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

  <hr style="border:none;border-top:1px solid var(--borde);margin:28px 0;">

  <h3 style="margin-top:0;">Documentos de la competición</h3>
  <p style="color:var(--gris);font-size:14px;max-width:60ch;margin-top:-8px;">
    Convocatoria, resultados oficiales... en PDF o Word (DOCX), hasta 20 MB cada uno.
  </p>

  <?php if ($documentos): ?>
  <ul style="list-style:none;padding:0;margin:0 0 18px;display:flex;flex-direction:column;gap:8px;">
    <?php foreach ($documentos as $doc): ?>
      <li style="display:flex;align-items:center;gap:10px;padding:10px 14px;border:1px solid var(--borde);border-radius:8px;">
        <span style="font-size:20px;">📄</span>
        <a href="../img/<?= e($doc['archivo']) ?>" target="_blank" style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($doc['nombre_original']) ?></a>
        <?php if (strtolower(pathinfo($doc['archivo'], PATHINFO_EXTENSION)) === 'pdf'): ?>
          <button type="submit" formaction="competicion_extraer_minutaje.php" name="documento_id" value="<?= (int)$doc['id'] ?>" class="editar">Intentar extraer horarios</button>
        <?php endif; ?>
        <button type="submit" formaction="competicion_documento_borrar.php" name="documento_id" value="<?= (int)$doc['id'] ?>" class="borrar" onclick="return confirm('¿Borrar este documento?');">Borrar</button>
      </li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>

  <div class="campo">
    <label for="documentos">Añadir documento(s) nuevos (PDF o DOCX, hasta 20 MB cada uno)</label>
    <input type="file" id="documentos" name="documentos[]" accept="application/pdf,.pdf,.docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" multiple>
  </div>

  <?php if ($errorMinutaje): ?>
    <p style="background:#FDECEC;color:#9B1C1C;padding:12px 16px;border-radius:8px;font-size:14px;"><?= e($errorMinutaje) ?></p>
  <?php endif; ?>

  <?php if ($id): ?>
  <hr style="border:none;border-top:1px solid var(--borde);margin:28px 0;">
  <h3 style="margin-top:0;">Horario de nuestras gimnastas</h3>
  <p style="color:var(--gris);font-size:14px;max-width:65ch;margin-top:-8px;">
    Si has subido el PDF de minutaje de la competición, puedes intentar
    extraer automáticamente a qué hora compite cada gimnasta del club
    (botón "Intentar extraer horarios" junto al documento, más
    arriba). Solo se tienen en cuenta las filas donde aparezca
    "Sakoneta" o "SAKONETA"; dentro de esas, si reconoce a alguien
    del plantel usa ese nombre tal cual, y si no, adivina el nombre
    igualmente (marcado como "No reconocida", para revisarlo con más
    atención). <strong>Esto es solo un borrador</strong>: revísalo,
    corrige lo que haga falta y confírmalo antes de que se publique
    en la ficha de la competición.
  </p>

  <?php if ($minutajeBorrador): ?>
  <div style="background:#FFF8E6;border:1px solid #F0D98C;border-radius:10px;padding:16px 18px;margin-bottom:20px;">
    <p style="margin-top:0;font-weight:600;">Borrador sin confirmar — revísalo antes de guardar:</p>
    <?php foreach ($minutajeBorrador as $i => $fila): ?>
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;flex-wrap:wrap;">
        <input type="checkbox" name="fila_incluir[<?= $i ?>]" value="1" checked style="width:auto;">
        <input type="hidden" name="fila_gimnasta_id[<?= $i ?>]" value="<?= (int)$fila['gimnasta_id'] ?>">
        <input type="text" name="fila_nombre[<?= $i ?>]" value="<?= e($fila['nombre']) ?>" style="max-width:220px;">
        <input type="text" name="fila_hora[<?= $i ?>]" value="<?= e($fila['hora'] ?? '') ?>" placeholder="HH:MM" style="max-width:90px;">
        <?php if (!$fila['gimnasta_id']): ?>
          <span style="font-size:11.5px;font-weight:600;color:#9B6B00;background:#FFF3D6;padding:2px 8px;border-radius:999px;">No reconocida en el plantel — revisa el nombre</span>
        <?php endif; ?>
        <span style="font-size:12.5px;color:var(--gris);flex:1;min-width:200px;">"<?= e($fila['dato_extra']) ?>"</span>
      </div>
    <?php endforeach; ?>
    <button type="submit" formaction="competicion_minutaje_guardar.php" name="competicion_id" value="<?= (int)$id ?>" class="btn" style="margin-top:8px;">Confirmar y guardar este horario</button>
  </div>
  <?php endif; ?>

  <?php if ($minutajeGuardado): ?>
  <table style="width:100%;border-collapse:collapse;margin-bottom:12px;">
    <?php foreach ($minutajeGuardado as $fila): ?>
      <tr style="border-bottom:1px solid var(--borde);">
        <td style="padding:8px 6px;font-weight:600;width:70px;"><?= e($fila['hora'] ?? '—') ?></td>
        <td style="padding:8px 6px;"><?= e($fila['nombre']) ?></td>
        <td style="padding:8px 6px;text-align:right;width:80px;">
          <button type="submit" formaction="competicion_minutaje_fila_borrar.php" name="fila_id" value="<?= (int)$fila['id'] ?>" class="borrar">Borrar</button>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
  <button type="submit" formaction="competicion_minutaje_borrar.php" name="competicion_id" value="<?= (int)$id ?>" class="borrar" onclick="return confirm('¿Borrar todo el horario de esta competición?');">Borrar todo el horario</button>
  <?php elseif (!$minutajeBorrador): ?>
    <p style="color:var(--gris);font-size:14px;">Todavía no hay ningún horario guardado para esta competición.</p>
  <?php endif; ?>
  <?php endif; ?>

  <button type="submit" class="btn" style="margin-top:24px;">Guardar competición</button>
  <a href="competiciones.php" class="btn secundario">Cancelar</a>
</form>

<?php require __DIR__ . '/includes/layout_footer.php'; ?>
