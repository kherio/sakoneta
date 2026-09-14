<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

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

$stmtFotos = $pdo->prepare('SELECT * FROM competicion_fotos WHERE competicion_id = ? ORDER BY orden ASC');
$stmtFotos->execute([$id]);
$fotos = $stmtFotos->fetchAll();

// La foto de portada encabeza la galería; el resto son las "otras fotos"
$fotoPrincipal = $competicion['imagen_portada'] ?: ($fotos[0]['archivo'] ?? 'competicion.svg');
$otrasFotos = array_filter($fotos, function ($f) use ($fotoPrincipal) { return $f['archivo'] !== $fotoPrincipal; });

$tituloPagina = $competicion['nombre'];
require __DIR__ . '/includes/header.php';
?>

<section class="franja-portada franja-competicion animar-scroll">
  <div class="franja-portada-imagen" data-parallax="0.08" data-parallax-limite="40" style="background-image:url('img/<?= e($fotoPrincipal) ?>');"></div>
  <div class="contenedor franja-portada-texto">
    <span class="tarjeta-competicion-categoria" style="position:static;display:inline-block;margin-bottom:10px;"><?= e($competicion['categoria']) ?></span>
    <h2><?= e($competicion['nombre']) ?></h2>
    <p>
      <?= e(formatearFecha($competicion['fecha'])) ?> · <?= e($competicion['lugar']) ?>
      <?php if ($competicion['disputada']): ?>
        · <?= e($competicion['resultado'] ?: t('disputada')) ?>
      <?php else: ?>
        · <?= t('pendiente') ?>
      <?php endif; ?>
    </p>
  </div>
</section>

<?php if (!empty($competicion['descripcion'])): ?>
<section class="seccion" style="padding-bottom:<?= $otrasFotos ? '0' : '64px' ?>;">
  <div class="contenedor">
    <div class="detalle-noticia">
      <?php foreach (explode("\n\n", $competicion['descripcion']) as $parrafo): ?>
        <?php if (trim($parrafo) !== ''): ?>
          <p><?= nl2br(e($parrafo)) ?></p>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
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
        <div class="galeria-parallax-item marco-parallax animar-scroll">
          <img src="img/<?= e($f['archivo']) ?>" alt="" data-parallax="0.07" data-parallax-limite="22">
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (!$otrasFotos && empty($competicion['descripcion'])): ?>
<section class="seccion">
  <div class="contenedor">
    <p><a href="competiciones.php">← <?= t('nav_competiciones') ?></a></p>
  </div>
</section>
<?php else: ?>
<section class="seccion" style="padding-top:0;">
  <div class="contenedor">
    <p><a href="competiciones.php"><?= t('volver_competiciones') ?></a></p>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
