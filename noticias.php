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
        <div class="marco-img"><img src="img/<?= e($n['imagen'] ?: 'competicion.svg') ?>" alt=""></div>
        <div class="cuerpo-tarjeta">
          <div class="fecha"><?= e(formatearFecha($n['fecha'])) ?></div>
          <h3><?= e($n['titulo']) ?></h3>
          <p><?= e($n['resumen']) ?></p>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
