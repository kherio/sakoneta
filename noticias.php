<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDb();
$paginaActual = 'noticias';
$tituloPagina = 'Noticias';

$noticias = $pdo->query('SELECT * FROM noticias WHERE publicado = 1 ORDER BY fecha DESC')->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<section class="seccion">
  <div class="contenedor">
    <div class="seccion-cabecera">
      <h2><?= t('seccion_noticias') ?></h2>
    </div>

    <?php if (!$noticias): ?>
      <p><?= t('sin_noticias') ?></p>
    <?php else: ?>
    <div class="pagina-noticias">
      <?php foreach ($noticias as $n): ?>
      <a href="noticia.php?id=<?= (int)$n['id'] ?>" class="tarjeta-noticia animar-scroll">
        <div class="marco-img"><img src="img/<?= e(rutaMiniatura($n['imagen']) ?: 'competicion.svg') ?>" alt="" loading="lazy"></div>
        <div class="cuerpo-tarjeta">
          <div class="fecha"><?= e(formatearFecha($n['fecha'])) ?></div>
          <h3><?= e(campoIdioma($n, 'titulo')) ?></h3>
          <p><?= e(campoIdioma($n, 'resumen')) ?></p>
          <span class="tarjeta-cta">Leer más <span class="tarjeta-flecha">→</span></span>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
