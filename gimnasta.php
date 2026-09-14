<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDb();
$paginaActual = 'gimnastas';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM gimnastas WHERE id = ?');
$stmt->execute([$id]);
$gimnasta = $stmt->fetch();

if (!$gimnasta) {
    http_response_code(404);
    $tituloPagina = 'Gimnasta no encontrada';
    require __DIR__ . '/includes/header.php';
    echo '<section class="seccion"><div class="contenedor"><p>Esta gimnasta no existe. <a href="gimnastas.php">Volver a gimnastas</a>.</p></div></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$stmtFotos = $pdo->prepare('SELECT * FROM gimnasta_fotos WHERE gimnasta_id = ? ORDER BY orden ASC');
$stmtFotos->execute([$id]);
$fotos = $stmtFotos->fetchAll();

$fotoPrincipal = $gimnasta['foto'] ?: ($fotos[0]['archivo'] ?? 'gimnasta-placeholder.svg');
$otrasFotos = array_filter($fotos, function ($f) use ($fotoPrincipal) { return $f['archivo'] !== $fotoPrincipal; });

$tituloPagina = $gimnasta['nombre'];
require __DIR__ . '/includes/header.php';
?>

<section class="franja-portada franja-competicion animar-scroll">
  <div class="franja-portada-imagen" data-parallax="0.08" data-parallax-limite="40" style="background-image:url('img/<?= e($fotoPrincipal) ?>');"></div>
  <div class="contenedor franja-portada-texto">
    <a href="categoria.php?nombre=<?= urlencode($gimnasta['categoria']) ?>" class="tarjeta-competicion-categoria" style="position:static;display:inline-block;margin-bottom:10px;color:#fff;"><?= e($gimnasta['categoria']) ?></a>
    <h2><?= e($gimnasta['nombre']) ?></h2>
    <p><?= e($gimnasta['modalidad']) ?><?= $gimnasta['aparato'] ? ' · ' . e($gimnasta['aparato']) : '' ?></p>
  </div>
</section>

<?php if ($otrasFotos): ?>
<section class="seccion">
  <div class="contenedor">
    <div class="seccion-cabecera">
      <h2><?= t('seccion_galeria') ?></h2>
      <a href="gimnastas.php"><?= t('nav_gimnastas') ?> →</a>
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
<?php else: ?>
<section class="seccion">
  <div class="contenedor">
    <p><a href="gimnastas.php">← <?= t('nav_gimnastas') ?></a></p>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
