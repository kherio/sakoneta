<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/i18n.php';

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
$descripcionOG = $gimnasta['nombre'] . ' · ' . $gimnasta['categoria'] . ' · ' . $gimnasta['modalidad'];
$origenSeguro = parse_url(SITE_URL, PHP_URL_SCHEME) . '://' . parse_url(SITE_URL, PHP_URL_HOST);
$imagenOG = $origenSeguro . '/img/' . $fotoPrincipal;
$migas = [['texto' => t('nav_gimnastas'), 'url' => 'gimnastas.php'], ['texto' => $gimnasta['nombre']]];
require __DIR__ . '/includes/header.php';
?>

<section class="franja-portada franja-competicion animar-scroll">
  <div class="franja-portada-imagen franja-hero-foto" data-parallax="0.08" data-parallax-limite="40" style="background-image:url('img/<?= e($fotoPrincipal) ?>');"></div>
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

<section class="seccion" style="padding-top:0;padding-bottom:48px;">
  <div class="contenedor">
    <a href="gimnastas.php" class="boton-volver" data-volver-listado="gimnastas.php"><?= t('volver_gimnastas') ?></a>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
