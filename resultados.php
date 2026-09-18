<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/i18n.php';

$pdo = getDb();
$paginaActual = 'competiciones';
$tituloPagina = 'Resultados';
$descripcionOG = 'Resultados de las competiciones ya disputadas por el club.';
$migas = [['texto' => t('nav_competiciones'), 'url' => 'competiciones.php'], ['texto' => 'Resultados']];

$competiciones = $pdo->query("
    SELECT c.*, (
        SELECT GROUP_CONCAT(categoria, ',') FROM (
            SELECT cc.categoria FROM competicion_categorias cc
            JOIN categorias cat ON cat.nombre = cc.categoria
            WHERE cc.competicion_id = c.id
            ORDER BY cat.orden ASC
        )
    ) AS categorias_lista,
    EXISTS (
        SELECT 1 FROM competicion_documentos cd
        WHERE cd.competicion_id = c.id AND cd.archivo LIKE '%.pdf'
    ) AS tiene_pdf
    FROM competiciones c
    WHERE c.disputada = 1
    ORDER BY c.fecha DESC
")->fetchAll();
$categoriasPresentes = [];
foreach ($competiciones as $c) {
    foreach (explode(',', $c['categorias_lista'] ?? '') as $cat) {
        if ($cat !== '' && !in_array($cat, $categoriasPresentes, true)) {
            $categoriasPresentes[] = $cat;
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<section class="seccion">
  <div class="contenedor">
    <div class="seccion-cabecera">
      <h2>Resultados</h2>
      <a href="competiciones.php">← Ver próximas competiciones</a>
    </div>

    <?php if (!$competiciones): ?>
      <p>Todavía no hay ninguna competición disputada. <a href="competiciones.php">Consulta las próximas</a>.</p>
    <?php else: ?>
    <?php if (count($categoriasPresentes) > 1): ?>
    <div class="filtro-categorias" data-filtro-objetivo="grid-filtrable-resultados">
      <button type="button" class="activo" data-categoria="todas"><?= t('filtro_todas') ?></button>
      <?php foreach ($categoriasPresentes as $cat): ?>
        <button type="button" data-categoria="<?= e($cat) ?>"><span class="punto-color-categoria" style="background:<?= e(colorCategoria($pdo, $cat)) ?>;"></span><?= e($cat) ?></button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="grid-competiciones" id="grid-filtrable-resultados">
      <?php foreach ($competiciones as $c): ?>
      <article class="tarjeta-competicion animar-scroll" data-categoria="<?= e($c['categorias_lista'] ?? '') ?>">
        <a href="competicion.php?id=<?= (int)$c['id'] ?>" class="tarjeta-competicion-enlace">
        <div class="tarjeta-competicion-foto">
          <img src="img/<?= e(rutaMiniatura($c['imagen_portada']) ?: 'competicion.svg') ?>" srcset="img/<?= e(rutaMiniatura($c['imagen_portada']) ?: 'competicion.svg') ?> 480w, img/<?= e($c['imagen_portada'] ?: 'competicion.svg') ?> 1600w" sizes="(max-width: 640px) 90vw, (max-width: 1024px) 45vw, 360px" alt="" data-parallax="0.05" data-parallax-limite="16" loading="lazy">
          <span class="tarjeta-competicion-categoria" style="background:<?= e(colorCategoria($pdo, explode(',', $c['categorias_lista'] ?? '')[0] ?? null)) ?>;"><?= e(str_replace(',', ' · ', $c['categorias_lista'] ?? '')) ?></span>
          <?php if ($c['tiene_pdf']): ?><span class="tarjeta-competicion-pdf">PDF</span><?php endif; ?>
        </div>
        <div class="tarjeta-competicion-cuerpo">
          <div class="fecha"><?= e(formatearFecha($c['fecha'])) ?></div>
          <h3><?= e(campoIdioma($c, 'nombre')) ?></h3>
          <p class="lugar"><?= e(campoIdioma($c, 'lugar')) ?></p>
          <div class="resultado"><?= e(campoIdioma($c, 'resultado') ?: t('disputada')) ?></div>
          <span class="pill jugado"><?= t('disputada') ?></span>
          <span class="tarjeta-cta">Ver detalles <span class="tarjeta-flecha">→</span></span>
        </div>
        </a>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
