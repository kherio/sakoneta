<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/i18n.php';

$pdo = getDb();
$paginaActual = 'competiciones';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM competiciones WHERE id = ?');
$stmt->execute([$id]);
$competicion = $stmt->fetch();

if (!$competicion) {
    http_response_code(404);
    $tituloPagina = 'Competición no encontrada';
    require __DIR__ . '/includes/header.php';
    echo '<section class="seccion"><div class="contenedor"><p>Esta competición no existe. <a href="competiciones.php">Volver a competiciones</a>.</p></div></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

// Vista previa ligera en JSON de una competición cualquiera (se
// mantiene por si hace falta en el futuro; la página normal ya no
// depende de esto para el swipe, ver más abajo).
if (isset($_GET['preview'])) {
    header('Content-Type: application/json');
    echo json_encode(datosVistaPreviaCompeticion($pdo, $id) ?: []);
    exit;
}

// Competición anterior/siguiente por fecha, para poder deslizar entre
// ellas en el móvil sin volver al listado
$stmtSiguiente = $pdo->prepare('SELECT id FROM competiciones WHERE fecha > ? ORDER BY fecha ASC LIMIT 1');
$stmtSiguiente->execute([$competicion['fecha']]);
$idSiguiente = $stmtSiguiente->fetchColumn();

$stmtAnterior = $pdo->prepare('SELECT id FROM competiciones WHERE fecha < ? ORDER BY fecha DESC LIMIT 1');
$stmtAnterior->execute([$competicion['fecha']]);
$idAnterior = $stmtAnterior->fetchColumn();

// Datos de la anterior/siguiente calculados aquí mismo, en el
// servidor, para incrustarlos directamente en la página (ver más
// abajo). Antes se pedían por una petición de red aparte (fetch) justo
// al cargar la página; si esa petición no llegaba a tiempo o fallaba
// en una conexión móvil floja, el swipe mostraba la foto (que carga
// por CSS normal) pero nunca llegaba a rellenar el texto.
$previaAnterior = $idAnterior ? datosVistaPreviaCompeticion($pdo, (int)$idAnterior) : null;
$previaSiguiente = $idSiguiente ? datosVistaPreviaCompeticion($pdo, (int)$idSiguiente) : null;

$esResultadoPodio = $competicion['disputada'] && $competicion['resultado']
    && preg_match('/\b(oro|plata|bronce|campe[oó]n|medalla|1º|1ª|primer[oa]?)\b/i', $competicion['resultado']);

$categoriasCompeticion = $pdo->prepare('
    SELECT cc.categoria FROM competicion_categorias cc
    JOIN categorias cat ON cat.nombre = cc.categoria
    WHERE cc.competicion_id = ?
    ORDER BY cat.orden ASC
');
$categoriasCompeticion->execute([$id]);
$categoriasCompeticion = $categoriasCompeticion->fetchAll(PDO::FETCH_COLUMN) ?: [$competicion['categoria']];

$stmtFotos = $pdo->prepare('SELECT * FROM competicion_fotos WHERE competicion_id = ? ORDER BY orden ASC');
$stmtFotos->execute([$id]);
$fotos = $stmtFotos->fetchAll();

// La foto de portada encabeza la galería; el resto son las "otras fotos"
$fotoPrincipal = $competicion['imagen_portada'] ?: ($fotos[0]['archivo'] ?? 'competicion.svg');
$otrasFotos = array_filter($fotos, function ($f) use ($fotoPrincipal) { return $f['archivo'] !== $fotoPrincipal; });

$stmtDocs = $pdo->prepare('SELECT * FROM competicion_documentos WHERE competicion_id = ? ORDER BY orden ASC');
$stmtDocs->execute([$id]);
$documentosCompeticion = $stmtDocs->fetchAll();

$stmtMinutaje = $pdo->prepare('SELECT * FROM competicion_minutaje WHERE competicion_id = ? ORDER BY hora ASC, orden ASC');
$stmtMinutaje->execute([$id]);
$minutajeCompeticion = $stmtMinutaje->fetchAll();

$tituloPagina = campoIdioma($competicion, 'nombre');
$descripcionCompeticionIdioma = campoIdioma($competicion, 'descripcion');
$descripcionOG = $descripcionCompeticionIdioma ? recortarTexto(trim(explode("\n\n", $descripcionCompeticionIdioma)[0]), 160) : (campoIdioma($competicion, 'lugar') . ' · ' . formatearFecha($competicion['fecha']));
$origenSeguro = parse_url(SITE_URL, PHP_URL_SCHEME) . '://' . parse_url(SITE_URL, PHP_URL_HOST);
$imagenOG = $origenSeguro . '/img/' . $fotoPrincipal;
$migas = [['texto' => t('nav_competiciones'), 'url' => 'competiciones.php'], ['texto' => campoIdioma($competicion, 'nombre')]];
require __DIR__ . '/includes/header.php';
?>

<section class="franja-portada franja-competicion animar-scroll">
  <div class="franja-portada-imagen franja-hero-foto" data-parallax="0.08" data-parallax-limite="40" style="background-image:url('img/<?= e($fotoPrincipal) ?>');background-position:<?= e(posicionCss($competicion['imagen_posicion'] ?? null)) ?>;"></div>
  <div class="contenedor franja-portada-texto">
    <div class="franja-competicion-subtitulo">
      <span class="tarjeta-competicion-categoria" style="position:static;display:inline-block;"><?= e(implode(' · ', $categoriasCompeticion)) ?></span>
      · <?= e(formatearFecha($competicion['fecha'])) ?><?= !empty($competicion['hora']) ? ' a las ' . e($competicion['hora']) : '' ?> · <?= e(campoIdioma($competicion, 'lugar')) ?>
      <?php if ($competicion['disputada']): ?>
        · <?= e(campoIdioma($competicion, 'resultado') ?: t('disputada')) ?>
      <?php else: ?>
        · <?= t('pendiente') ?>
      <?php endif; ?>
    </div>
    <h2><?= e(campoIdioma($competicion, 'nombre')) ?></h2>
  </div>
</section>

<?php if ($descripcionCompeticionIdioma !== ''): ?>
<section class="seccion" style="padding-bottom:<?= $otrasFotos ? '0' : '64px' ?>;">
  <div class="contenedor">
    <div class="detalle-noticia">
      <?php foreach (explode("\n\n", $descripcionCompeticionIdioma) as $parrafo): ?>
        <?php if (trim($parrafo) !== ''): ?>
          <p><?= nl2br(e($parrafo)) ?></p>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($minutajeCompeticion): ?>
<section class="seccion" style="padding-top:0;">
  <div class="contenedor">
    <h3>Horario de nuestras gimnastas</h3>
    <table class="tabla-horario-minutaje">
      <?php foreach ($minutajeCompeticion as $fila): ?>
        <tr>
          <td class="tabla-horario-hora"><?= $fila['hora'] ? e($fila['hora']) : '—' ?></td>
          <td><?= e($fila['nombre']) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
</section>
<?php endif; ?>

<?php if ($documentosCompeticion): ?>
<section class="seccion" style="padding-top:0;">
  <div class="contenedor">
    <h3>Documentos</h3>
    <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:10px;max-width:60ch;">
      <?php foreach ($documentosCompeticion as $doc): ?>
        <li>
          <a href="img/<?= e($doc['archivo']) ?>" target="_blank" rel="noopener" style="display:flex;align-items:center;gap:10px;padding:12px 16px;border:1px solid var(--borde-suave);border-radius:var(--radio-chico);color:inherit;text-decoration:none;background:var(--superficie);">
            <span style="font-size:22px;">📄</span>
            <span style="flex:1;"><?= e($doc['nombre_original']) ?></span>
            <span style="font-size:13px;color:var(--gris-texto);">Descargar ↓</span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<?php if ($otrasFotos): ?>
<section class="seccion">
  <div class="contenedor">
    <div class="seccion-cabecera">
      <h2><?= t('seccion_galeria') ?></h2>
      <a href="competiciones.php"><?= t('volver_competiciones') ?></a>
    </div>
    <div class="galeria-parallax">
      <?php foreach ($otrasFotos as $f): ?>
        <?php if ($f['tipo'] === 'video'): ?>
          <div class="galeria-video">
            <video controls preload="metadata"><source src="img/<?= e($f['archivo']) ?>"></video>
          </div>
        <?php else: ?>
          <div class="galeria-parallax-item marco-parallax animar-scroll">
            <img src="img/<?= e($f['archivo']) ?>" alt="" data-parallax="0.07" data-parallax-limite="22" loading="lazy">
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (!$otrasFotos && $descripcionCompeticionIdioma === ''): ?>
<section class="seccion">
  <div class="contenedor"></div>
</section>
<?php else: ?>
<section class="seccion" style="padding-top:0;">
  <div class="contenedor"></div>
</section>
<?php endif; ?>

<section class="seccion" style="padding-top:0;padding-bottom:48px;">
  <div class="contenedor">
    <a href="competiciones.php" class="boton-volver" data-volver-listado="competiciones.php,resultados.php"><?= t('volver_competiciones') ?></a>
  </div>
</section>

<div id="swipe-competicion"
     data-podio="<?= $esResultadoPodio ? '1' : '' ?>"
     data-anterior="<?= $idAnterior ? 'competicion.php?id=' . (int)$idAnterior : '' ?>"
     data-siguiente="<?= $idSiguiente ? 'competicion.php?id=' . (int)$idSiguiente : '' ?>"
     style="display:none;" aria-hidden="true"></div>

<?php foreach (['anterior' => $previaAnterior, 'siguiente' => $previaSiguiente] as $lado => $previa): ?>
<div class="vista-previa-swipe vista-previa-swipe-<?= $lado ?>" id="vista-previa-<?= $lado ?>" aria-hidden="true">
  <?php if ($previa): ?>
  <nav class="migas-pan vista-previa-swipe-migas">
    <div class="contenedor">
      <a href="index.php"><?= t('nav_inicio') ?></a>
      <span class="migas-separador">›</span>
      <a href="competiciones.php"><?= t('nav_competiciones') ?></a>
      <span class="migas-separador">›</span>
      <span class="migas-actual"><?= e($previa['nombre']) ?></span>
    </div>
  </nav>
  <div class="vista-previa-swipe-cabecera-foto">
    <div class="vista-previa-swipe-foto" style="background-image:url('<?= e($previa['imagen']) ?>');background-position:<?= e($previa['posicion']) ?>;"></div>
    <div class="vista-previa-swipe-texto">
      <div class="franja-competicion-subtitulo">
        <span class="tarjeta-competicion-categoria" style="position:static;display:inline-block;"><?= e(implode(' · ', $previa['categorias'])) ?></span>
        · <?= e(formatearFecha($previa['fecha'])) ?><?= !empty($previa['hora']) ? ' a las ' . e($previa['hora']) : '' ?> · <?= e($previa['lugar']) ?>
        <?php if ($previa['disputada']): ?>
          · <?= e($previa['resultado'] ?: t('disputada')) ?>
        <?php else: ?>
          · <?= t('pendiente') ?>
        <?php endif; ?>
      </div>
      <h2><?= e($previa['nombre']) ?></h2>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php endforeach; ?>

<?php if ($idAnterior || $idSiguiente): ?>
<div class="aviso-swipe" id="aviso-swipe">
  <span class="aviso-swipe-mano">👆</span>
  <span>Desliza para ver otra competición</span>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
