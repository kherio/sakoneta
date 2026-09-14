<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDb();
$paginaActual = 'categoria';

$nombreCategoria = trim($_GET['nombre'] ?? '');
if ($nombreCategoria === '') {
    redirigir('index.php');
}

$stmt = $pdo->prepare('SELECT * FROM categorias WHERE nombre = ?');
$stmt->execute([$nombreCategoria]);
$categoria = $stmt->fetch();

$stmtGimnastas = $pdo->prepare('SELECT * FROM gimnastas WHERE categoria = ? ORDER BY orden ASC');
$stmtGimnastas->execute([$nombreCategoria]);
$gimnastas = $stmtGimnastas->fetchAll();

$stmtCompeticiones = $pdo->prepare('SELECT * FROM competiciones WHERE categoria = ? ORDER BY fecha DESC');
$stmtCompeticiones->execute([$nombreCategoria]);
$competiciones = $stmtCompeticiones->fetchAll();

$fotos = [];
if ($categoria) {
    $stmtFotos = $pdo->prepare('SELECT * FROM categoria_fotos WHERE categoria_id = ? ORDER BY orden ASC');
    $stmtFotos->execute([$categoria['id']]);
    $fotos = $stmtFotos->fetchAll();
}
$fotoPrincipal = ($categoria['imagen_portada'] ?? null) ?: ($fotos[0]['archivo'] ?? 'competicion.svg');
$otrasFotos = array_filter($fotos, function ($f) use ($fotoPrincipal) { return $f['archivo'] !== $fotoPrincipal; });

$tituloPagina = 'Categoría ' . $nombreCategoria;
require __DIR__ . '/includes/header.php';
?>

<section class="franja-portada franja-competicion animar-scroll">
  <div class="franja-portada-imagen" data-parallax="0.08" data-parallax-limite="40" style="background-image:url('img/<?= e($fotoPrincipal) ?>');"></div>
  <div class="contenedor franja-portada-texto">
    <h2><?= e($nombreCategoria) ?></h2>
    <p><?= count($gimnastas) ?> gimnastas · <?= count($competiciones) ?> competiciones</p>
  </div>
</section>

<?php if ($gimnastas): ?>
<section class="seccion">
  <div class="contenedor">
    <div class="seccion-cabecera">
      <h2><?= t('seccion_gimnastas') ?></h2>
    </div>
    <div class="grid-plantilla">
      <?php foreach ($gimnastas as $g): ?>
      <a href="gimnasta.php?id=<?= (int)$g['id'] ?>" class="tarjeta-jugador animar-scroll" style="display:block;">
        <div class="marco-img"><img src="img/<?= e($g['foto'] ?: 'gimnasta-placeholder.svg') ?>" alt="<?= e($g['nombre']) ?>"></div>
        <div class="info">
          <div class="dorsal"><?= e($g['categoria']) ?></div>
          <h4><?= e($g['nombre']) ?></h4>
          <div class="posicion"><?= e($g['modalidad']) ?><?= $g['aparato'] ? ' · ' . e($g['aparato']) : '' ?></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($competiciones): ?>
<section class="seccion" style="padding-top:0;">
  <div class="contenedor">
    <div class="seccion-cabecera">
      <h2><?= t('seccion_competiciones') ?></h2>
    </div>
    <div class="grid-competiciones">
      <?php foreach ($competiciones as $c): ?>
      <article class="tarjeta-competicion animar-scroll">
        <a href="competicion.php?id=<?= (int)$c['id'] ?>" class="tarjeta-competicion-foto">
          <img src="img/<?= e($c['imagen_portada'] ?: 'competicion.svg') ?>" alt="">
        </a>
        <div class="tarjeta-competicion-cuerpo">
          <div class="fecha"><?= e(formatearFecha($c['fecha'])) ?></div>
          <h3><a href="competicion.php?id=<?= (int)$c['id'] ?>" style="color:inherit;"><?= e($c['nombre']) ?></a></h3>
          <p class="lugar"><?= e($c['lugar']) ?></p>
          <?php if ($c['disputada']): ?>
            <span class="pill jugado"><?= t('disputada') ?></span>
          <?php else: ?>
            <span class="pill pendiente"><?= t('pendiente') ?></span>
          <?php endif; ?>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($otrasFotos): ?>
<section class="seccion" style="padding-top:0;">
  <div class="contenedor">
    <div class="seccion-cabecera">
      <h2><?= t('seccion_galeria') ?></h2>
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

<?php if (!$gimnastas && !$competiciones): ?>
<section class="seccion">
  <div class="contenedor">
    <p>Todavía no hay gimnastas ni competiciones en esta categoría.</p>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
