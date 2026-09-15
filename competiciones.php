<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDb();
$paginaActual = 'competiciones';
$tituloPagina = 'Próximas competiciones';

$competiciones = $pdo->query("SELECT * FROM competiciones WHERE disputada = 0 ORDER BY fecha ASC")->fetchAll();
$categoriasPresentes = [];
foreach ($competiciones as $c) {
    if ($c['categoria'] && !in_array($c['categoria'], $categoriasPresentes, true)) {
        $categoriasPresentes[] = $c['categoria'];
    }
}

require __DIR__ . '/includes/header.php';
?>

<section class="seccion">
  <div class="contenedor">
    <div class="seccion-cabecera">
      <h2><?= t('seccion_competiciones') ?></h2>
      <a href="resultados.php">Ver competiciones anteriores →</a>
    </div>

    <?php if (!$competiciones): ?>
      <p>No hay ninguna competición pendiente por ahora. <a href="resultados.php">Consulta los resultados de las ya disputadas</a>.</p>
    <?php else: ?>
    <?php if (count($categoriasPresentes) > 1): ?>
    <div class="filtro-categorias" data-filtro-objetivo="grid-filtrable-competiciones">
      <button type="button" class="activo" data-categoria="todas"><?= t('filtro_todas') ?></button>
      <?php foreach ($categoriasPresentes as $cat): ?>
        <button type="button" data-categoria="<?= e($cat) ?>"><?= e($cat) ?></button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="grid-competiciones" id="grid-filtrable-competiciones">
      <?php foreach ($competiciones as $c): ?>
      <article class="tarjeta-competicion animar-scroll" data-categoria="<?= e($c['categoria']) ?>">
        <a href="competicion.php?id=<?= (int)$c['id'] ?>" class="tarjeta-competicion-foto">
          <img src="img/<?= e($c['imagen_portada'] ?: 'competicion.svg') ?>" alt="" data-parallax="0.05" data-parallax-limite="16">
          <span class="tarjeta-competicion-categoria"><?= e($c['categoria']) ?></span>
        </a>
        <div class="tarjeta-competicion-cuerpo">
          <div class="fecha"><?= e(formatearFecha($c['fecha'])) ?></div>
          <h3><a href="competicion.php?id=<?= (int)$c['id'] ?>" style="color:inherit;"><?= e($c['nombre']) ?></a></h3>
          <p class="lugar"><?= e($c['lugar']) ?></p>
          <?php if ($c['disputada']): ?>
            <div class="resultado"><?= e($c['resultado'] ?: t('disputada')) ?></div>
            <span class="pill jugado"><?= t('disputada') ?></span>
          <?php else: ?>
            <span class="pill pendiente"><?= t('pendiente') ?></span>
          <?php endif; ?>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
